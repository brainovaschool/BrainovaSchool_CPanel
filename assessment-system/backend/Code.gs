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
   - This is a shared-secret gate (one token everyone's browser sends),
     not per-user login. Anyone who has the token can write anything.
     A later step layers a signed, per-user, expiring token on top of
     this for real access control (students see only their own data,
     etc). Until then, treat this as good enough for a school's own
     diagnostic tool, not as hardened security.
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
  if (typeof v !== 'string' && typeof v !== 'number') return v;
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
const SNAPSHOT_TABS_ = ['students', 'parents', 'assessments', 'bank', 'settings', 'supervision'];

function writeSnapshot_(payload) {
  SNAPSHOT_TABS_.forEach(tab => {
    const list = payload[tab];
    if (Array.isArray(list)) list.forEach(rec => upsertRow_(tab, rec));
  });
}

function writeAttempt_(att, an) {
  // One self-contained row per attempt: everything needed to
  // reconstruct it lives here, so a fresh bootstrap never has to
  // stitch data back together across tabs.
  upsertRow_('attempts', {
    id: att.id, asmtId: att.asmtId, studentId: att.studentId, studentName: att.studentName, grade: att.grade,
    application: att.application, courseId: att.courseId || '', mode: att.mode, model: att.model, title: att.title,
    started: att.started, finished: att.finished, durationMs: att.durationMs, attemptNo: att.attemptNo,
    released: !!att.released, preview: !!att.preview,
    reviewNotes: (att.review && att.review.notes) || '', reviewOverride: (att.review && att.review.override) || '',
    reviewFinalized: !!(att.review && att.review.finalized),
    supervision: att.supervision || null, stopReason: att.stopReason || '',
    trace: att.trace || [], items: att.items || [], responses: att.responses || [], events: att.events || []
  });

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
      { id: att.id, attemptId: att.id, studentId: att.studentId, overallIdx: an.overallIdx }, an.placement));
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

function handleWrite_(kind, payload) {
  if (kind === 'snapshot') return writeSnapshot_(payload);
  if (kind === 'attempt') return writeAttempt_(payload.attempt || payload, payload.analysis);
  if (kind === 'response') return writeResponse_(payload);
  throw new Error('unknown kind: ' + kind);
}

/* ---------- read path (what a fresh browser loads on startup) ---------- */

function buildBootstrapState_() {
  const list = tab => readAll_(tab);
  const settingsRows = list('settings');
  const settings = settingsRows.length ? safeParse_(settingsRows[0].json) : null;
  const supervision = {};
  list('supervision').forEach(r => { const rec = Object.assign({}, r); const id = rec.id; delete rec.id; supervision[id] = rec; });
  return {
    v: 1, sample: false,
    students: list('students'),
    parents: list('parents'),
    assessments: list('assessments'),
    bank: list('bank'),
    settings: settings,
    attempts: list('attempts'),
    supervision: supervision,
    sessions: []
  };
}

/* ---------- auth + entry points ---------- */

function checkToken_(token) {
  const secret = PropertiesService.getScriptProperties().getProperty('API_TOKEN');
  return !!secret && token === secret;
}

function jsonOut_(obj) {
  return ContentService.createTextOutput(JSON.stringify(obj)).setMimeType(ContentService.MimeType.JSON);
}

function doGet(e) {
  const token = (e.parameter && e.parameter.token) || '';
  if (!checkToken_(token)) return jsonOut_({ ok: false, error: 'unauthorized' });
  const action = e.parameter && e.parameter.action;
  if (action === 'bootstrap') return jsonOut_({ ok: true, state: buildBootstrapState_() });
  return jsonOut_({ ok: false, error: 'unknown action' });
}

function doPost(e) {
  let body;
  try { body = JSON.parse(e.postData.contents); } catch (err) { return jsonOut_({ ok: false, error: 'bad json' }); }
  if (!checkToken_(body.token)) return jsonOut_({ ok: false, error: 'unauthorized' });

  const lock = LockService.getScriptLock();
  let gotLock = false;
  try {
    gotLock = lock.tryLock(10000);
    if (!gotLock) return jsonOut_({ ok: false, error: 'busy, please retry' });
    if (body.action === 'write') { handleWrite_(body.kind, body.payload); return jsonOut_({ ok: true }); }
    return jsonOut_({ ok: false, error: 'unknown action' });
  } catch (err) {
    return jsonOut_({ ok: false, error: String(err && err.message || err) });
  } finally {
    if (gotLock) lock.releaseLock();
  }
}
