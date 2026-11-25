/* =====================================================================
   BRAINOVA ASSESSMENT SYSTEM - Google Sheets backend (Apps Script)
   =====================================================================

   This is the whole backend: a handful of generic helpers that read
   and write Google Sheets by HEADER NAME rather than by a fixed column
   list, plus two entry points (doGet / doPost) that the front end's
   source/api.js talks to.

   WHY IT'S BUILT THIS WAY (read this before editing)
   ----------------------------------------------------------------
   Nowhere below does the code say "column 3 is grade" or list out
   every field a student/attempt/question can have. Instead:
     - upsertRow(tabName, record) looks at the record's own keys. Any
       key that doesn't already have a column gets one appended on the
       spot. Existing columns it doesn't mention are left untouched.
     - readAll(tabName) reads the header row to find out what the
       columns ARE, and returns plain objects shaped however the sheet
       currently looks.
   That means: if the front end starts sending a question with a new
   "hint" field, or a student record gains a "phone" field, nothing
   HERE needs to change - a new column just appears the first time
   that field is saved. The only time this file needs an edit is a
   genuinely new TAB (see TAB fan-out in writeAttempt, and the single
   line in SNAPSHOT key handling) or a new ACTION. Both are small,
   localized changes, never a redesign.

   WHAT THIS CANNOT DO (said plainly, not hidden)
   ----------------------------------------------------------------
   - Two keys now gate access (see authenticate_): the original shared
     API_TOKEN (anyone who has it can do anything - that's still how
     your own admin browser works when you open the app directly,
     outside the LMS), and AUTH_SECRET, which verifies a per-user signed
     token the LMS hands a student/teacher/parent on login. A verified
     student/parent token gets filtered to their own (or their linked
     children's) released data only (see buildBootstrapState_ and
     writeAllowed_). This is still "good enough for a school's own
     tool," not independently audited security - there's no rate
     limiting or logging of failed attempts, for instance.
   - Answer keys for questions are already shipped to every browser in
     the app's own JS bundle today, and saving a question's full record
     here doesn't make that any more or less exposed than it already
     is. Moving answer-checking server-side is future work, not part
     of this step.
   - All writes are serialized with a lock (see doPost), so a burst of
     simultaneous requests (e.g. a live class all submitting near the
     same moment) queues up safely rather than corrupting a row, but a
     very large simultaneous burst will simply queue and take longer -
     Apps Script has no way to make Sheets writes run in parallel.
   ===================================================================== */

/* ---------- small generic sheet engine (no entity-specific knowledge) ---------- */

function getSheet_(name) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  let sh = ss.getSheetByName(name);
  if (!sh) {
    sh = ss.insertSheet(name);
    sh.appendRow(['id']);
    sh.setFrozenRows(1);
  }
  return sh;
}

function headerIndex_(sheet) {
  const last = sheet.getLastColumn();
  if (last === 0) return {};
  const headers = sheet.getRange(1, 1, 1, last).getValues()[0];
  const idx = {};
  headers.forEach((h, i) => { if (h) idx[String(h)] = i + 1; });
  return idx;
}

// Adds any column the record needs that the sheet doesn't have yet.
// Returns true if it changed anything (caller should re-read the index).
function ensureColumns_(sheet, idx, keys) {
  let changed = false;
  keys.forEach(k => {
    if (!(k in idx)) {
      const col = sheet.getLastColumn() + 1;
      sheet.getRange(1, col).setValue(k);
      idx[k] = col;
      changed = true;
    }
  });
  return changed;
}

function findRowById_(sheet, idx, id) {
  if (!idx.id) return -1;
  const n = sheet.getLastRow();
  if (n < 2) return -1;
  const vals = sheet.getRange(2, idx.id, n - 1, 1).getValues();
  for (let i = 0; i < vals.length; i++) if (String(vals[i][0]) === String(id)) return i + 2;
  return -1;
}

// Nested arrays/objects are stored as JSON text in their own cell, so
// a cell always holds a plain value and the sheet stays readable.
function flatten_(record) {
  const out = {};
  Object.keys(record).forEach(k => {
    const v = record[k];
    out[k] = (v !== null && typeof v === 'object') ? JSON.stringify(v) : v;
  });
  return out;
}

function safeParse_(v) {
  if (typeof v !== 'string') return v;
  // flatten_() only ever JSON.stringifies an object or array (see its
  // own definition above) - every plain string/number/boolean cell is
  // written exactly as-is, so it can never legitimately come back as
  // JSON text. Every one of those plain cells - a question's text, its
  // subject, a title, a source - used to still run the full JSON.parse
  // + catch machinery and fail, for every single cell of every single
  // row, on every single read. With the bank alone at 1000+ rows across
  // ~20 columns, that was tens of thousands of wasted parse attempts
  // per request - a large, measured share of why a full bootstrap read
  // had grown to take several seconds. Skipping straight past anything
  // that doesn't even start with { or [ - the only two characters
  // JSON.stringify can ever start with - returns the exact same result
  // for every cell, just without ever attempting (and catching) a parse
  // that could only ever have failed.
  const t = v.trim();
  // Every character JSON.stringify can ever start a value with (object,
  // array, quoted string, the literals true/false/null, or a number) is
  // one of these, case-sensitively - JSON's true/false/null are always
  // lowercase, so a cell that starts with "True..." (capital T, common
  // in this app's own "True or false: ..." question text) can never
  // have been produced by JSON.stringify and is safely skipped too.
  if (!/^[{["tfn0-9-]/.test(t)) return v;
  try { return JSON.parse(v); } catch (e) { return v; }
}

// Insert or update one record, by its "id" field. Columns the record
// doesn't mention keep whatever value they already had (partial
// updates, like a per-answer save, never blank out other fields).
function upsertRow_(tabName, record) {
  if (record.id === undefined || record.id === null || record.id === '') throw new Error('upsertRow_: record needs an id (' + tabName + ')');
  const flat = flatten_(record);
  const sheet = getSheet_(tabName);
  let idx = headerIndex_(sheet);
  if (ensureColumns_(sheet, idx, Object.keys(flat))) idx = headerIndex_(sheet);
  const rowNum = findRowById_(sheet, idx, flat.id);
  const width = sheet.getLastColumn();
  let rowArr = new Array(width).fill('');
  if (rowNum > 0) rowArr = sheet.getRange(rowNum, 1, 1, width).getValues()[0];
  Object.keys(flat).forEach(k => { rowArr[idx[k] - 1] = flat[k] === null || flat[k] === undefined ? '' : flat[k]; });
  if (rowNum > 0) sheet.getRange(rowNum, 1, 1, width).setValues([rowArr]);
  else sheet.appendRow(rowArr);
}

function readAll_(tabName) {
  const sheet = getSheet_(tabName);
  const idx = headerIndex_(sheet);
  const n = sheet.getLastRow();
  if (n < 2) return [];
  const width = sheet.getLastColumn();
  const rows = sheet.getRange(2, 1, n - 1, width).getValues();
  return rows.filter(r => r.some(c => c !== '')).map(r => {
    const obj = {};
    Object.keys(idx).forEach(k => { obj[k] = safeParse_(r[idx[k] - 1]); });
    return obj;
  });
}

/* ---------- write paths ---------- */

// Collections that are plain arrays of {id, ...} in the app's state
// (students, parents, assessments, bank, plus settings/supervision
// already turned into that shape by the front end before sending).
// Add a name here - nothing else - to start syncing a new collection.
//
// 'attempts' is included too, even though a full attempt is normally
// written through the separate kind:'attempt' path (writeAttempt_,
// below) - that one always sends the WHOLE attempt object, so it's
// safe there. This path is for the Laravel app's own server-to-server
// calls (see AssessmentSheetClient::releaseAttempt()) that only ever
// send a couple of fields (e.g. {id, released}) for an attempt that
// already exists - upsertManyRows_ merges by column name and leaves
// every other existing column on that row untouched, so a partial
// write here can never blank out the rest of the attempt.
const SNAPSHOT_TABS_ = ['students', 'parents', 'assessments', 'bank', 'settings', 'supervision', 'pathways', 'attempts', 'simpleSets'];

function writeSnapshot_(payload) {
  SNAPSHOT_TABS_.forEach(tab => {
    const list = payload[tab];
    if (Array.isArray(list) && list.length) upsertManyRows_(tab, list);
  });
}

// Batched version of upsertRow_, for writing many records to one tab
// in a single pass - upsertRow_ itself does 3-4 separate Sheet API
// calls per record (read the header, look up the row, read the row,
// write the row), so a snapshot with a few hundred records across all
// tabs could add up to well over a thousand of those round trips and
// run long enough to hit the script lock's 10s wait or simply time
// out, leaving every change behind it stuck in the browser's retry
// queue. This reads the whole tab once, applies every change in
// memory, and writes it back in at most two range calls total,
// regardless of how many records are in the batch.
function upsertManyRows_(tabName, records) {
  const sheet = getSheet_(tabName);
  let idx = headerIndex_(sheet);
  const keys = {};
  records.forEach(r => Object.keys(flatten_(r)).forEach(k => { keys[k] = true; }));
  if (ensureColumns_(sheet, idx, Object.keys(keys))) idx = headerIndex_(sheet);

  const width = sheet.getLastColumn();
  const lastRow = sheet.getLastRow();
  const existing = lastRow >= 2 ? sheet.getRange(2, 1, lastRow - 1, width).getValues() : [];
  const idCol = idx.id - 1;
  const rowByKey = {};
  existing.forEach((row, i) => { if (row[idCol] !== '') rowByKey[String(row[idCol])] = i; });

  const toAppend = [];
  records.forEach(rec => {
    if (rec.id === undefined || rec.id === null || rec.id === '') return; // same guard as upsertRow_
    const flat = flatten_(rec);
    const key = String(flat.id);
    const i = rowByKey.hasOwnProperty(key) ? rowByKey[key] : null;
    const rowArr = i !== null ? existing[i] : new Array(width).fill('');
    Object.keys(flat).forEach(k => { rowArr[idx[k] - 1] = flat[k] === null || flat[k] === undefined ? '' : flat[k]; });
    if (i !== null) existing[i] = rowArr; else { rowByKey[key] = existing.length + toAppend.length; toAppend.push(rowArr); }
  });

  if (existing.length) sheet.getRange(2, 1, existing.length, width).setValues(existing);
  if (toAppend.length) sheet.getRange(lastRow + 1, 1, toAppend.length, width).setValues(toAppend);
}

function writeAttempt_(att, an) {
  // One self-contained row per attempt: everything needed to
  // reconstruct it lives here, so a fresh bootstrap never has to
  // stitch data back together across tabs.
  //
  // 'released' is deliberately OMITTED here unless the client itself
  // already knows it's released (e.g. an instant-feedback assessment) -
  // never force-set to false. A lead's submission fires two independent
  // writes to this same row at nearly the same moment: this one (the
  // student's own full attempt, via the browser) and Laravel's
  // server-to-server release flag (AssessmentSheetClient::releaseAttempt(),
  // a {id, released:true} partial merge - see SNAPSHOT_TABS_ above).
  // upsertRow_ only touches the columns actually present in the record
  // it's given, so leaving 'released' out when it's false lets whichever
  // of these two writes lands SECOND preserve what the other one set,
  // regardless of which one actually reaches the Sheet first - sending
  // released:false here unconditionally used to stomp Laravel's
  // released:true right back to false whenever this write happened to
  // land after it.
  const attemptRow = {
    id: att.id, asmtId: att.asmtId, studentId: att.studentId, studentName: att.studentName, grade: att.grade,
    application: att.application, courseId: att.courseId || '', mode: att.mode, model: att.model, title: att.title,
    started: att.started, finished: att.finished, durationMs: att.durationMs, attemptNo: att.attemptNo,
    preview: !!att.preview,
    reviewNotes: (att.review && att.review.notes) || '', reviewOverride: (att.review && att.review.override) || '',
    reviewFinalized: !!(att.review && att.review.finalized),
    supervision: att.supervision || null, stopReason: att.stopReason || '',
    trace: att.trace || [], items: att.items || [], responses: att.responses || [], events: att.events || []
  };
  if (att.released) attemptRow.released = true;
  upsertRow_('attempts', attemptRow);

  // The same data, fanned out one-row-per-item, purely so a human (or
  // the LMS, later) can read/filter it directly without parsing JSON
  // blobs. Deliberately redundant with the row above - see file header.
  (att.responses || []).forEach(r => upsertRow_('responses', Object.assign(
    { id: att.id + '_' + r.seq, attemptId: att.id, studentId: att.studentId, asmtId: att.asmtId, partial: false }, r)));
  (att.events || []).forEach((ev, i) => upsertRow_('events', Object.assign({ id: att.id + '_ev' + i, attemptId: att.id }, ev)));

  if (an) {
    (an.topics || []).forEach(t => upsertRow_('topic_results', Object.assign(
      { id: att.id + '_' + t.topic, attemptId: att.id, studentId: att.studentId }, t)));
    upsertRow_('placements', Object.assign(
      { id: att.id, attemptId: att.id, studentId: att.studentId, overallIdx: an.overallIdx,
        // The actual numeric score - overallIdx/placement are the
        // qualitative level ("Proficient"), this is the plain percentage
        // marks, for anywhere that needs that instead (the LMS dashboard).
        scorePct: an.scorePct, accuracy: an.accuracy },
      an.placement));
  }
}

// A single in-progress adaptive answer, saved before the attempt it
// belongs to has even been finalised. writeAttempt_ later upserts the
// same id with the final data, which simply confirms/overwrites this.
function writeResponse_(p) {
  upsertRow_('responses', Object.assign(
    { id: p.attemptId + '_' + p.resp.seq, attemptId: p.attemptId, studentId: p.studentId, asmtId: p.asmtId, partial: true },
    p.resp));
}

// A single record in a single collection - the one write a signed-in
// student/parent may make outside their own attempts (see
// writeAllowed_), so they can register themselves in the roster on
// first login without ever touching anyone else's row.
function writeSelf_(p) { upsertRow_(p.collection, p.record); }

function handleWrite_(kind, payload) {
  if (kind === 'snapshot') return writeSnapshot_(payload);
  if (kind === 'attempt') return writeAttempt_(payload.attempt || payload, payload.analysis);
  if (kind === 'response') return writeResponse_(payload);
  if (kind === 'self') return writeSelf_(payload);
  throw new Error('unknown kind: ' + kind);
}

/* ---------- read path (what a fresh browser loads on startup) ---------- */

function truthy_(v) { return v === true || v === 'true' || v === 'TRUE' || v === 1; }

// IDs are compared as strings throughout this file: one might come
// from a URL parameter (always a string), another from JSON (whatever
// type the sender used), another from a Sheet cell (a number unless
// someone's formatted that column as text) - string comparison is the
// one thing that's never surprised by which of those it's looking at.
function idEq_(a, b) { return String(a) === String(b); }
function idIn_(list, v) { return (list || []).some(x => idEq_(x, v)); }

// One student's most recent RELEASED, non-preview attempt, boiled down
// to the handful of fields the LMS shows on that student's profile
// (see Step 8: AssessmentSystemController / App\Services\AssessmentReport
// on the Laravel side). Returns null if there's nothing released yet.
function latestReleasedReport_(studentId) {
  // studentId arrives as a URL parameter (always a string), while the
  // Sheet may have stored it as a real number if it was ever written
  // from a raw numeric id - compare as strings so neither type trips
  // this up.
  const attempts = readAll_('attempts')
    .filter(a => String(a.studentId) === String(studentId) && truthy_(a.released) && !truthy_(a.preview))
    .sort((a, b) => (b.finished || 0) - (a.finished || 0));
  if (!attempts.length) return null;
  const att = attempts[0];
  const pl = readAll_('placements').find(p => idEq_(p.attemptId, att.id)) || null;
  // scorePct (marks out of the assessment's own total) is the more
  // familiar number for a fixed-questions test; accuracy (% of graded
  // questions answered correctly) is what an adaptive placement test
  // has instead, since it has no fixed total. Either way this ends up
  // as a plain 0-100 number, never a fraction.
  const pct = pl && pl.scorePct != null ? pl.scorePct : (pl && pl.accuracy != null ? pl.accuracy : null);
  return {
    assessmentTitle: att.title || '',
    finished: att.finished || null,
    scorePct: pct != null ? Math.round(pct * 100) : null,
    overallLabel: pl ? (pl.overallLabel || null) : null,
    recommendation: (pl && Array.isArray(pl.lines) && pl.lines.length) ? pl.lines[0] : null
  };
}

// writeAttempt_ (above) flattens an attempt's review into three plain
// columns (reviewNotes/reviewOverride/reviewFinalized) so a human can
// read them directly in the Sheet - but every reader in the front end
// (reportBody, the dashboard, exports, Pathways) expects the original
// shape engine.js's makeAttempt() builds: a nested review object, and a
// subjectList the Sheet never stores a column for at all (it's derived
// once from the topics at submit time, not re-saved). Reconstructing
// both here means any attempt that went out to the Sheet and came back
// is shaped exactly like one that never left the browser - without
// this, opening its report throws the moment it reads att.review.*.
function reshapeAttempt_(a) {
  const out = Object.assign({}, a, {
    review: { notes: a.reviewNotes || '', override: a.reviewOverride || '', finalized: !!a.reviewFinalized }
  });
  delete out.reviewNotes; delete out.reviewOverride; delete out.reviewFinalized;
  if (!out.subjectList || !out.subjectList.length) {
    const subs = {};
    (a.items || []).forEach(it => { if (it && it.subject) subs[it.subject] = true; });
    out.subjectList = Object.keys(subs);
  }
  if (!out.scores) out.scores = {};
  return out;
}

// null claims (legacy/admin shared-token access) or role admin/teacher
// get everything, same as before this step. A signed-in student/parent
// gets only their own (or their linked children's) RELEASED attempts,
// and only their own roster rows - never another family's data, even
// though it's sitting in the same spreadsheet.
//
// assessments/bank/settings are left unfiltered for every role: the
// adaptive engine runs and grades entirely in the browser (see engine.js
// and this file's own header comment), so the device administering a
// test already needs the question bank, including its answer keys, to
// function at all - that's a limitation of this static-front-end
// design, not something a read filter here can fix.
function buildBootstrapState_(claims) {
  const list = tab => readAll_(tab);
  const settingsRows = list('settings');
  const settings = settingsRows.length ? safeParse_(settingsRows[0].json) : null;
  const supervision = {};
  list('supervision').forEach(r => { const rec = Object.assign({}, r); const id = rec.id; delete rec.id; supervision[id] = rec; });

  // One JSON-blob row per attempt (same trick as the settings tab's
  // single row) - keyed here by attempt id so it can be attached
  // straight onto the matching attempt below, instead of travelling as
  // its own top-level collection the front end would have to re-join.
  const pathwaysById_ = {};
  list('pathways').forEach(r => { pathwaysById_[r.id] = safeParse_(r.json); });

  let students = list('students'), parents = list('parents'), attempts = list('attempts').map(reshapeAttempt_);
  const assessments = list('assessments');
  let simpleSets = list('simpleSets');

  // A student/parent never needs any OTHER simple set's full question
  // list (each one can run to 60-100 questions, embedded whole) - only
  // the one or two actually assigned to them. Unfiltered, every single
  // page load for every visitor downloaded every simple set that
  // exists, which only got slower as more were added this way. A
  // teacher/admin session (claims === null) still gets everything, same
  // as assessments/bank already do.
  function ownSimpleSetIds_(matches) {
    const ids = new Set();
    assessments.forEach(a => { if (a.simpleSetId && matches(a.assignedTo || [])) ids.add(a.simpleSetId); });
    return ids;
  }

  if (claims && claims.role === 'student') {
    attempts = attempts.filter(a => idEq_(a.studentId, claims.uid) && truthy_(a.released));
    students = students.filter(s => idEq_(s.id, claims.uid));
    parents = parents.filter(p => idIn_(p.childIds, claims.uid));
    const mine = ownSimpleSetIds_(assignedTo => idIn_(assignedTo, claims.uid));
    simpleSets = simpleSets.filter(s => mine.has(s.id));
  } else if (claims && claims.role === 'parent') {
    const kids = claims.children || [];
    attempts = attempts.filter(a => idIn_(kids, a.studentId) && truthy_(a.released));
    students = students.filter(s => idIn_(kids, s.id));
    parents = parents.filter(p => idEq_(p.id, claims.uid));
    const mine = ownSimpleSetIds_(assignedTo => assignedTo.some(id => idIn_(kids, id)));
    simpleSets = simpleSets.filter(s => mine.has(s.id));
  }

  // Learning pathways: teachers/admins (claims === null, or an explicit
  // teacher/admin login) see whatever has been generated, draft or not.
  // A parent only ever sees one once it has been shared - never while
  // it is still a draft or waiting on the administrator. A student
  // never sees their own pathway at all, however far along it is.
  attempts.forEach(a => {
    const pw = pathwaysById_[a.id];
    if (!pw) return;
    if (claims && claims.role === 'student') return;
    if (claims && claims.role === 'parent' && pw.status !== 'shared') return;
    a.pathways = pw;
  });

  return {
    v: 1, sample: false,
    students: students,
    parents: parents,
    assessments: assessments,
    bank: list('bank'),
    simpleSets: simpleSets,
    settings: settings,
    attempts: attempts,
    supervision: supervision,
    sessions: []
  };
}

/* ---------- auth + entry points ----------
   Two independent ways in, either is enough on its own:
     - API_TOKEN: the one shared secret from Step 3's setup. Anyone who
       has it can do anything - this is what your own admin browser
       still uses when you open the app directly (not through the LMS)
       to use Settings.
     - a per-user signed token (AUTH_SECRET): what the LMS hands a
       student/teacher/parent on login (see AssessmentSystemController
       in the Laravel app). It proves WHO is asking, so requests that
       carry one get scoped to that person's own role automatically -
       no shared secret ever needs to reach a student's browser at all. */

function base64UrlEncodeBytes_(bytes) {
  return Utilities.base64Encode(bytes).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function base64UrlDecodeToString_(str) {
  str = String(str).replace(/-/g, '+').replace(/_/g, '/');
  while (str.length % 4) str += '=';
  return Utilities.newBlob(Utilities.base64Decode(str)).getDataAsString();
}

// Verifies the HMAC-SHA256 signature itself (same scheme as the Laravel
// side) and checks expiry. Returns the decoded claims {uid,role,name,
// children?,exp} if the token is genuine and still valid, else null -
// never throws, so a malformed token just fails authentication.
function verifyAuthToken_(token) {
  if (!token || token.indexOf('.') < 0) return null;
  const secret = PropertiesService.getScriptProperties().getProperty('AUTH_SECRET');
  if (!secret) return null;
  const parts = token.split('.');
  const payload = parts[0], sig = parts[1];
  const expected = base64UrlEncodeBytes_(Utilities.computeHmacSha256Signature(payload, secret));
  if (expected !== sig) return null;
  let claims;
  try { claims = JSON.parse(base64UrlDecodeToString_(payload)); } catch (e) { return null; }
  if (!claims || !claims.role || claims.uid === undefined || !claims.exp) return null;
  if (claims.exp * 1000 < Date.now()) return null;
  return claims;
}

// claims === null means "legacy/admin access via the shared token" -
// unrestricted, same as every step before this one. A non-null claims
// means a verified per-user login - callers use its role/uid/children
// to decide what that specific person may read or write.
function authenticate_(token, authToken) {
  const claims = verifyAuthToken_(authToken);
  if (claims) return { ok: true, claims: claims };
  const secret = PropertiesService.getScriptProperties().getProperty('API_TOKEN');
  if (secret && token === secret) return { ok: true, claims: null };
  return { ok: false, claims: null };
}

function jsonOut_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

function doGet(e) {
  const action = e.parameter && e.parameter.action;
  const token = (e.parameter && e.parameter.token) || '';

  if (action === 'report') {
    // A third, separate credential (REPORT_TOKEN) - read-only, meant
    // for a server-to-server call from the LMS itself (never a
    // browser), so if it ever leaked it could only read one student's
    // own latest released summary at a time, never write anything or
    // read a full snapshot the way API_TOKEN/AUTH_SECRET can.
    const reportSecret = PropertiesService.getScriptProperties().getProperty('REPORT_TOKEN');
    if (!reportSecret || token !== reportSecret) return jsonOut_({ ok: false, error: 'unauthorized' });
    const studentId = e.parameter && e.parameter.studentId;
    if (!studentId) return jsonOut_({ ok: false, error: 'studentId required' });
    return jsonOut_({ ok: true, report: latestReleasedReport_(studentId) });
  }

  const auth = authenticate_(token, (e.parameter && e.parameter.authToken) || '');
  if (!auth.ok) return jsonOut_({ ok: false, error: 'unauthorized' });
  if (action === 'bootstrap') return jsonOut_({ ok: true, state: buildBootstrapState_(auth.claims) });
  // Laravel's AssessmentSheetClient::fetchPublishedAssessments() only
  // ever needs the assessments tab, but was calling full 'bootstrap' -
  // which also reads the entire bank (1000s of rows) and every simple
  // set's whole question list just to throw all of that away. As both
  // grew, that one extra read started pushing this call close enough to
  // Laravel's 10-second timeout to fail intermittently - exactly the
  // "sometimes shows, sometimes doesn't" the public booking page was
  // doing. This reads only the one tab that call actually needs.
  if (action === 'assessments') return jsonOut_({ ok: true, assessments: readAll_('assessments') });
  return jsonOut_({ ok: false, error: 'unknown action' });
}

// A signed-in student may only write their OWN attempt/response, never
// someone else's id, and never a full administrative snapshot. A
// teacher/admin (or the legacy shared-token path, claims === null) can
// write anything, same as every step before this one - teachers submit
// attempts on behalf of a whole room during a live class.
function writeAllowed_(claims, kind, payload) {
  if (!claims) return true;
  if (claims.role === 'teacher' || claims.role === 'admin') return true;

  // Either role may register/update exactly their own roster row (how
  // identity.js adds a brand-new LMS student/parent to the Sheet, and
  // keeps a parent's childIds current) - never anyone else's.
  if (kind === 'self') {
    const wantsOwnStudentRow = claims.role === 'student' && payload.collection === 'students';
    const wantsOwnParentRow = claims.role === 'parent' && payload.collection === 'parents';
    return (wantsOwnStudentRow || wantsOwnParentRow) && payload.record && idEq_(payload.record.id, claims.uid);
  }

  if (claims.role !== 'student') return false; // parents never submit assessment data
  if (kind === 'snapshot') return false;
  const sid = (payload && payload.studentId) || (payload && payload.attempt && payload.attempt.studentId);
  return idEq_(sid, claims.uid);
}

function doPost(e) {
  let body;
  try { body = JSON.parse(e.postData.contents); } catch (err) { return jsonOut_({ ok: false, error: 'bad json' }); }
  const auth = authenticate_(body.token, body.authToken);
  if (!auth.ok) return jsonOut_({ ok: false, error: 'unauthorized' });
  if (body.action === 'write' && !writeAllowed_(auth.claims, body.kind, body.payload)) {
    return jsonOut_({ ok: false, error: 'forbidden' });
  }

  const lock = LockService.getScriptLock();
  let gotLock = false;
  try {
    gotLock = lock.tryLock(15000);
    if (!gotLock) return jsonOut_({ ok: false, error: 'busy, please retry' });
    if (body.action === 'write') { handleWrite_(body.kind, body.payload); return jsonOut_({ ok: true }); }
    return jsonOut_({ ok: false, error: 'unknown action' });
  } catch (err) {
    return jsonOut_({ ok: false, error: String(err && err.message || err) });
  } finally {
    if (gotLock) lock.releaseLock();
  }
}
