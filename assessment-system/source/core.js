/* ===================== CORE: state, persistence, seed data, helpers ===================== */
const LS_KEY = 'brainova.demo.v1';
let S = null;              // persisted data (demo store)
const U = { role: 'teacher', view: 'dash', params: {}, filters: { range: 'all', subject: '', grade: '', course: '', asmt: '', topic: '', level: '', student: '' }, draft: null, step: 1, run: null, live: null, toast: '', bankSel: null, bankFilter: { q: '', subject: '', topic: '', diff: '', type: '', status: '', grade: '' }, studentId: null, parentId: null, resTab: 'attempts', stuSel: null, lms: null, stuBulk: [] };
let anCache = new WeakMap();

const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const uid = p => p + '_' + Math.random().toString(36).slice(2, 9);
const pct = x => x == null ? '—' : Math.round(x * 100) + '%';
const fmtDate = t => new Date(t).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
const fmtDur = ms => { const s = Math.round(ms / 1000), m = Math.floor(s / 60); return m ? `${m}m ${String(s % 60).padStart(2, '0')}s` : `${s}s`; };
const clock = ms => { ms = Math.max(0, ms); const s = Math.ceil(ms / 1000); return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0'); };
// The shape seedBase() gives settings on a brand-new install. Used to
// repair S.settings if it's ever missing/null - e.g. the Sheet's own
// "settings" tab was still empty the first time this browser fetched
// a snapshot from it, which used to wipe out the local defaults
// (including the proficiency levels every report depends on) with
// nothing at all. See ensureSample() and init()'s remote-merge.
function defaultSettings() {
  return { levels: DEFAULT_LEVELS.map(l => ({ ...l })), school: 'Brainova', lms: { baseUrl: '', token: '', tenant: 'brainova', status: 'Not configured' }, sharing: JSON.parse(JSON.stringify(DEFAULT_SHARING)), assessmentMode: 'old' };
}
const levels = () => S.settings.levels;
const lvName = i => i == null ? 'Needs assessment' : levels()[i].name;
const lvPill = i => i == null ? '<span class="lv lvn">Needs assessment</span>' : `<span class="lv lv${i}">${esc(levels()[i].name)}</span>`;
const sumBy = (a, f) => a.reduce((s, x) => s + f(x), 0);
const avg = a => a.length ? sumBy(a, x => x) / a.length : null;

/* ---------- persistence (localStorage is always the offline cache; source/api.js
   syncs the same data to the Google Sheets backend once configured) ---------- */
function persistLocal() { try { localStorage.setItem(LS_KEY, JSON.stringify(S)); } catch (e) { /* storage blocked or full: app keeps working in memory */ } }
// A signed-in student/parent (see identity.js) is only ever allowed to
// write their own attempt/response on the backend, never a full
// administrative snapshot - so this skips sending one at all for them,
// instead of enqueuing a write that would just come back "forbidden"
// and retry forever.
function save() {
  // Re-applied on every single save, not just when the mode toggle
  // itself is clicked - the old Builder's own edit/publish screen is
  // reachable (and fully usable) no matter which procedure is active,
  // so publishing or re-saving an old assessment while "simple" is
  // active must not be able to quietly undo its suppression. Idempotent
  // either way (see applyAssessmentModeFlags itself).
  applyAssessmentModeFlags();
  persistLocal();
  if (window.BrainovaAPI && (!U.lms || U.lms.role === 'admin' || U.lms.role === 'teacher')) window.BrainovaAPI.scheduleSync(S);
}
function load() { try { const t = localStorage.getItem(LS_KEY); if (t) { const d = JSON.parse(t); if (d && d.v === 1) return d; } } catch (e) { } return null; }

/* ---------- lookups ---------- */
const asmtById = id => S.assessments.find(a => a.id === id);
const studentById = id => S.students.find(s => s.id === id);
const attById = id => S.attempts.find(a => a.id === id) || (U.previewAtt && U.previewAtt.id === id ? U.previewAtt : null);
const courseOf = a => a && a.courseId ? COURSES.find(c => c.id === a.courseId) : null;
function AN(att) { let r = anCache.get(att); if (!r) { r = analyze(att, levels(), courseOf(att)); anCache.set(att, r); } return r; }
function finalLevelText(att, an) { return (att.review && att.review.override) ? att.review.override : (an.placement.tier || an.placement.grade || '—'); }
function subjectsOf(a) { return [...new Set(a.topics.map(t => t.subject))]; }
// Mirrors exactly what AssessmentBookingController::createLeadAndSend()
// does server-side: a public visitor is matched to a published,
// leadIntake assessment that shares BOTH a grade and a subject with
// them. If two published, leadIntake assessments ever share both, the
// server picks one silently (whichever the Sheet returns first) - this
// flags that situation here, before it ever reaches a real family.
// An empty syllabus list means "any syllabus" (the same "leave empty
// to ignore" convention grades already uses), so it overlaps with
// everything, including another assessment's own empty list.
function syllabusOverlap(a, b) {
  if (!(a.syllabus || []).length || !(b.syllabus || []).length) return true;
  return a.syllabus.some(s => b.syllabus.includes(s));
}
function leadIntakeConflicts(a) {
  if (!a.leadIntake || !a.grades.length) return [];
  const subs = new Set(subjectsOf(a));
  return S.assessments.filter(o => o.id !== a.id && o.status === 'published' && o.leadIntake
    && o.grades.some(g => a.grades.includes(g)) && subjectsOf(o).some(s => subs.has(s)) && syllabusOverlap(a, o));
}
// Repairs text that was correctly written in UTF-8 (×, ÷, −, curly
// quotes, dashes...) but got read somewhere along the way - a CSV
// import, a copy/paste, a re-save in Excel - as if it were Windows-1252,
// turning each multi-byte character into 2-3 garbled ones (×  becomes
// "Ã—", an apostrophe becomes "â€™", and so on). That garbled TEXT is
// what actually got stored, so fixing the import code afterwards never
// un-breaks it - only reversing the same byte-for-byte misreading does.
// Standard Windows-1252 mapping for bytes 0x80-0x9F (0xA0-0xFF is
// identical to Unicode there, and 0x00-0x7F is plain ASCII either way).
const CP1252_HI = { 0x80: '€', 0x82: '‚', 0x83: 'ƒ', 0x84: '„', 0x85: '…', 0x86: '†', 0x87: '‡', 0x88: 'ˆ', 0x89: '‰', 0x8A: 'Š', 0x8B: '‹', 0x8C: 'Œ', 0x8E: 'Ž', 0x91: '‘', 0x92: '’', 0x93: '“', 0x94: '”', 0x95: '•', 0x96: '–', 0x97: '—', 0x98: '˜', 0x99: '™', 0x9A: 'š', 0x9B: '›', 0x9C: 'œ', 0x9E: 'ž', 0x9F: 'Ÿ' };
const CP1252_REV = {}; Object.keys(CP1252_HI).forEach(b => { CP1252_REV[CP1252_HI[b]] = +b; });
function decodeMojibake(str) {
  if (!str) return null;
  const bytes = [];
  for (const ch of str) {
    const cp = ch.codePointAt(0);
    if (cp < 0x80 || (cp >= 0xA0 && cp <= 0xFF)) bytes.push(cp);
    else if (CP1252_REV[ch] !== undefined) bytes.push(CP1252_REV[ch]);
    else return null; // not a character Windows-1252 could have produced - leave untouched
  }
  try { const fixed = new TextDecoder('utf-8', { fatal: true }).decode(new Uint8Array(bytes)); return fixed !== str ? fixed : null; }
  catch (e) { return null; }
}
const MOJIBAKE_FIELDS = ['text', 'explanation', 'objective', 'passage'];
function scanMojibake() {
  const fixes = [];
  S.bank.forEach(q => {
    MOJIBAKE_FIELDS.forEach(f => { const r = decodeMojibake(q[f]); if (r) fixes.push({ id: q.id, field: f, before: q[f], after: r }); });
    (q.options || []).forEach((o, i) => { const r = decodeMojibake(o.t); if (r) fixes.push({ id: q.id, field: 'opt' + i, before: o.t, after: r }); });
  });
  return fixes;
}
function applyMojibakeFixes(fixes) {
  const byId = new Map(); fixes.forEach(fx => { if (!byId.has(fx.id)) byId.set(fx.id, []); byId.get(fx.id).push(fx); });
  S.bank.forEach(q => { const fs = byId.get(q.id); if (!fs) return; fs.forEach(fx => { if (fx.field.startsWith('opt')) q.options[+fx.field.slice(3)].t = fx.after; else q[fx.field] = fx.after; }); });
}
// ---- Simple assessment procedure (temporary, parallel to the Builder) ----
// One CSV of ready-made questions per grade+subject, three difficulty
// tiers, no topic/grade-range tagging to get wrong. Deterministic id
// from grade+subject, so uploading again for the same pair replaces it
// instead of creating a second, competing one.
function simpleSetKey(grade, subject) { return 'simpleset-' + grade + '-' + String(subject).replace(/[^a-z0-9]+/gi, '').toLowerCase(); }
// A real assessment, built and kept in sync automatically from a
// simple set, so everything that already understands an assessment -
// assigning students, the public booking page's own grade+subject
// matching, results, reports - works for it unchanged. Only its
// questions come from a different place (see buildSimpleQuiz in
// engine.js) instead of the Question bank.
function syncSimpleCompanion(set, contentReplaced) {
  const id = 'asmt-' + set.id, i = S.assessments.findIndex(a => a.id === id);
  // resultVis/feedback default to 'release' - fine for the Builder,
  // where a teacher reviews and releases each attempt by hand, but
  // nobody ever does that for this flow. Without instant release here,
  // Code.gs's own bootstrap filter (buildBootstrapState_ in Code.gs)
  // never sends a parent an unreleased attempt at all - not "waiting",
  // just silently absent, which is exactly what looked like "my child's
  // submitted test vanished" (it was never released, so it was never
  // actually assigned/sent in the first place).
  const a = newAsmt({ id, title: (set.title || '').trim() || `Grade ${set.grade} ${set.subject} (Simple)`, application: 'school', grades: [set.grade], topics: [{ subject: set.subject, topic: 'General' }], model: 'fixed', simpleSetId: set.id,
    resultVis: 'instant', feedback: 'instant',
    assignedTo: (i >= 0 && !contentReplaced) ? S.assessments[i].assignedTo : [], created: i >= 0 ? S.assessments[i].created : Date.now() });
  if (i >= 0) S.assessments[i] = a; else S.assessments.unshift(a);
}
// Each simple set has its own "offer on the public booking page" flag
// (off by default - a new set isn't exposed just by uploading it), the
// same convention the old Builder already uses. The mode toggle is the
// master switch: while "simple" is active, a companion assessment's
// EFFECTIVE leadIntake is that flag; while "old" is active, no
// companion is ever eligible regardless of the flag. It also actively
// SUSPENDS every old, hand-built assessment's own leadIntake for as
// long as "simple" is active - otherwise one left live from before the
// switch silently competes with (and can win over) the new system,
// which is exactly the surprise this exists to prevent - and restores
// each one's own setting, unchanged, the moment "old" is switched back
// on. a.leadIntakeWanted is just where that original value waits.
function applyAssessmentModeFlags() {
  const simple = (S.settings.assessmentMode || 'old') === 'simple';
  S.assessments.forEach(a => {
    if (a.simpleSetId) {
      const set = S.simpleSets.find(s => s.id === a.simpleSetId);
      a.status = simple ? 'published' : 'draft';
      a.leadIntake = simple && !!(set && set.leadIntake);
      // Re-applied here too (not just in syncSimpleCompanion) so a
      // companion created before this existed gets fixed on its own,
      // the next time anything at all is saved - no need to re-save
      // that specific simple set by hand.
      a.resultVis = 'instant'; a.feedback = 'instant';
    } else if (simple) {
      if (a.leadIntakeWanted === undefined) a.leadIntakeWanted = a.leadIntake;
      a.leadIntake = false;
    } else if (a.leadIntakeWanted !== undefined) {
      a.leadIntake = a.leadIntakeWanted; a.leadIntakeWanted = undefined;
    }
  });
}
function attemptsOf(asmtId, studentId) { return S.attempts.filter(a => a.asmtId === asmtId && a.studentId === studentId && !a.preview); }
function accomFor(sid) { const s = studentById(sid); return s && s.accom ? s.accom : { extraPct: 0, largeText: false }; }

/* ---------- seed ---------- */
function newAsmt(over) {
  return Object.assign({ id: uid('asm'), title: '', purpose: 'Diagnostic', application: 'school', courseId: '', grades: [5], ageGroup: '9–11 years', stretch: 1, level: 'Grade level', outcomes: '', instructions: 'Read each question carefully and answer on your own. You may not use AI tools, other people or other devices.',
    topics: [], excludeObj: [], model: 'fixed', dist: { S: 6, H: 8, E: 6 }, adaptive: { min: 10, max: 20, minPer: 3, sdTarget: 0.7, startB: -0.5, probe: true }, mode: 'independent', durationMin: 30, perQuestionSec: 0, nav: 'free', allowBack: true, allowSkip: true, allowChange: true,
    shuffleQ: false, shuffleOpts: true, attempts: 1, autoSubmit: true, feedback: 'release', resultVis: 'release', passPct: 0, parentSupervision: false, fullscreen: true, autoAdvance: false, status: 'draft', assignedTo: [], leadIntake: false, syllabus: [], pinnedIds: [], created: Date.now() }, over || {});
}
// Same five choices the public booking form itself offers (see
// assessment-register.blade.php / book-assessment.blade.php) - kept in
// sync by hand since an assessment's syllabus tag and a parent's
// syllabus answer need to speak the same values to ever match.
const SYLLABUSES = ['SNC', 'Cambridge', 'Oxford', 'Pearson Edexcel', 'Other'];
const mathTopics = ts => ts.map(t => ({ subject: 'Mathematics', topic: t }));
function seedBase() {
  const now = Date.now(), day = 86400000;
  const roster = [['Ayesha Khan', 5, 0.9, { Division: -0.7 }], ['Hamza Ali', 5, 0.0, {}, { guessy: true }], ['Fatima Noor', 5, 0.4, { Fractions: 0.6 }], ['Zain Ahmed', 4, -0.5, {}], ['Maryam Siddiqui', 4, 0.2, { Decimals: -1.0 }],
    ['Ibrahim Raza', 5, -1.2, { Addition: 0.5 }], ['Sara Malik', 6, 1.4, {}], ['Usman Tariq', 6, 0.1, { Division: -1.1, Addition: 0.9 }], ['Hira Raza', 3, -0.9, {}], ['Ahmed Bilal', 3, -0.2, {}],
    ['Zoya Imran', 7, 0.6, {}], ['Rayyan Shah', 8, 1.0, { Debugging: -0.6 }], ['Anaya Qureshi', 8, 0.3, { Loops: -0.9 }], ['Daniyal Hussain', 9, -0.4, {}], ['Laiba Javed', 10, 1.6, {}], ['Mustafa Aziz', 6, -0.1, {}]];
  const students = roster.map((r, i) => ({ id: 'BRV-26-' + String(101 + i), name: r[0], grade: r[1], parentId: null, accom: { extraPct: 0, largeText: false }, _ab: { theta: r[2], byTopic: r[3], ...(r[4] || {}) } }));
  const byName = n => students.find(s => s.name === n);
  byName('Maryam Siddiqui').accom = { extraPct: 25, largeText: false }; byName('Ibrahim Raza').accom = { extraPct: 50, largeText: true };
  const parents = []; const mk = (name, kids) => { const p = { id: uid('par'), name, childIds: kids.map(k => byName(k).id) }; kids.forEach(k => byName(k).parentId = p.id); parents.push(p); };
  mk('Mrs. Nadia Khan', ['Ayesha Khan']); mk('Mr. Imran Ali', ['Hamza Ali']); mk('Mrs. Saima Raza', ['Ibrahim Raza', 'Hira Raza']); mk('Mr. Tariq Mehmood', ['Usman Tariq']); mk('Mrs. Rubina Malik', ['Sara Malik']);
  ['Fatima Noor', 'Zain Ahmed', 'Maryam Siddiqui', 'Ahmed Bilal', 'Zoya Imran', 'Mustafa Aziz'].forEach(n => mk('Parent of ' + n.split(' ')[0], [n]));
  const bank = buildBank();
  const gradeIds = gs => students.filter(s => gs.includes(s.grade)).map(s => s.id);
  const A1 = newAsmt({ id: 'asm_g5math', title: 'Grade 5 Mathematics Term Diagnostic', purpose: 'Diagnostic', application: 'school', grades: [5], topics: mathTopics(MATH_TOPICS), model: 'fixed', dist: { S: 6, H: 10, E: 8 }, durationMin: 40, parentSupervision: true,
    outcomes: 'Place each student by topic across Terms 1–3 and find gaps before the new term.', status: 'published', assignedTo: gradeIds([4, 5]), created: now - 40 * day });
  const A2 = newAsmt({ id: 'asm_mathadm', title: 'Mathematics Course Admission (Adaptive)', purpose: 'Admission', application: 'course', courseId: 'c-mathfun', grades: [3, 4, 5, 6], ageGroup: '8–12 years', topics: mathTopics(['Addition', 'Subtraction', 'Multiplication', 'Division', 'Fractions']), model: 'adaptive',
    adaptive: { min: 10, max: 20, minPer: 3, sdTarget: 0.7, startB: -0.5, probe: true }, durationMin: 35, parentSupervision: true, outcomes: 'Recommend a foundation, intermediate or advanced start for Mathematics Fundamentals.', status: 'published', assignedTo: gradeIds([3, 4, 5, 6]), created: now - 35 * day });
  const A3 = newAsmt({ id: 'asm_engplace', title: 'English Grammar Placement', purpose: 'Placement', application: 'course', courseId: 'c-eng', grades: [5, 6, 7], topics: [{ subject: 'English', topic: 'Parts of Speech' }, { subject: 'English', topic: 'Tenses' }], model: 'fixed', dist: { S: 4, H: 4, E: 4 }, durationMin: 20, status: 'published', assignedTo: gradeIds([5, 6, 7]), created: now - 30 * day });
  const A4 = newAsmt({ id: 'asm_python', title: 'Python Starter Placement', purpose: 'Placement', application: 'course', courseId: 'c-code', grades: [7, 8, 9, 10], ageGroup: '12–16 years', topics: ['Variables & Output', 'Conditionals', 'Loops', 'Debugging'].map(t => ({ subject: 'Computer Science', topic: t })), model: 'adaptive',
    adaptive: { min: 10, max: 18, minPer: 3, sdTarget: 0.7, startB: -0.5, probe: true }, durationMin: 30, status: 'published', assignedTo: gradeIds([7, 8, 9, 10]), created: now - 25 * day });
  const A5 = newAsmt({ id: 'asm_scilive', title: 'Science Quick Check (Live Class)', purpose: 'Topic proficiency', application: 'school', grades: [5], topics: ['Living Things', 'Matter', 'Forces & Energy'].map(t => ({ subject: 'Science', topic: t })), model: 'fixed', dist: { S: 3, H: 4, E: 3 }, mode: 'live', perQuestionSec: 30, allowBack: false, status: 'published', assignedTo: gradeIds([5]), created: now - 3 * day });
  const A6 = newAsmt({ id: 'asm_readdraft', title: 'Reading Comprehension Grade 5', purpose: 'Academic', application: 'school', grades: [5], topics: [{ subject: 'English', topic: 'Reading Comprehension' }], model: 'fixed', dist: { S: 2, H: 2, E: 2 }, durationMin: 15, status: 'draft', created: now - 2 * day });
  const assessments = [A1, A2, A3, A4, A5, A6];
  const attempts = [];
  const run = (a, name, daysAgo) => { const st = byName(name), at = simulateAttempt(a, bank, st, st._ab, a.id + name, now - daysAgo * day); at.preview = false; attempts.push(at); return at; };
  [['Ayesha Khan', 21], ['Hamza Ali', 21], ['Fatima Noor', 20], ['Ibrahim Raza', 19], ['Maryam Siddiqui', 18], ['Zain Ahmed', 6]].forEach(([n, d], i) => { const at = run(A1, n, d); at.released = i < 4; });
  [['Sara Malik', 14], ['Usman Tariq', 13], ['Hira Raza', 12], ['Ahmed Bilal', 12], ['Mustafa Aziz', 9]].forEach(([n, d], i) => { const at = run(A2, n, d); at.released = i !== 4; });
  [['Fatima Noor', 10], ['Sara Malik', 10], ['Zoya Imran', 8], ['Usman Tariq', 8]].forEach(([n, d]) => { const at = run(A3, n, d); at.released = true; });
  [['Zoya Imran', 7], ['Rayyan Shah', 6], ['Anaya Qureshi', 6], ['Daniyal Hussain', 5], ['Laiba Javed', 4]].forEach(([n, d], i) => { const at = run(A4, n, d); at.released = i < 3; });
  // a couple of unusual-interruption events, recorded without any cheating label
  attempts[1].events.push({ t: attempts[1].started + 420000, type: 'tab-hidden', note: 'Page was hidden for 14 s' });
  students.forEach(s => delete s._ab);
  attempts.forEach(a => { a.review = { notes: '', override: '', finalized: a.released }; });
  attempts[0].review.notes = 'Strong across whole numbers. Keep an eye on division word problems.';
  return { v: 1, sample: true, settings: { levels: DEFAULT_LEVELS.map(l => ({ ...l })), school: 'Brainova', lms: { baseUrl: '', token: '', tenant: 'brainova', status: 'Not configured' } }, bank, students, parents, assessments, attempts, simpleSets: [], supervision: {}, sessions: [] };
}

/* ---------- toast + downloads ---------- */
let toastTimer = 0;
function toast(msg) { U.toast = msg; const el = document.getElementById('toast'); if (el) { el.textContent = msg; el.hidden = false; } clearTimeout(toastTimer); toastTimer = setTimeout(() => { U.toast = ''; const e = document.getElementById('toast'); if (e) e.hidden = true; }, 3200); }
// Saves a file straight through the browser's own download mechanism -
// works in any real browser with no special permission, unlike an
// earlier version of this that only worked inside Claude's own preview
// tool (window.claude.use('downloads')), which silently did nothing on
// the live site.
async function saveFile(name, data) {
  try {
    const type = /\.json$/i.test(name) ? 'application/json'
      : /\.csv$/i.test(name) ? 'text/csv'
      : /\.html?$/i.test(name) ? 'text/html'
      : /\.md$/i.test(name) ? 'text/markdown'
      : 'text/plain';
    const blob = new Blob([data], { type: type + ';charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = name; a.style.display = 'none';
    document.body.appendChild(a); a.click(); a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    toast('Saved ' + name);
  } catch (e) { toast('Could not save the file' + (e && e.message ? ': ' + e.message : '')); }
}
const csvCell = v => { v = v == null ? '' : String(v); return /[",\n\r]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v; };
const toCSV = rows => '﻿' + rows.map(r => r.map(csvCell).join(',')).join('\r\n');
function parseCSV(text) {
  const rows = []; let row = [], cur = '', q = false; text = text.replace(/^﻿/, '');
  for (let i = 0; i < text.length; i++) {
    const c = text[i];
    if (q) { if (c === '"') { if (text[i + 1] === '"') { cur += '"'; i++; } else q = false; } else cur += c; }
    else if (c === '"') q = true; else if (c === ',') { row.push(cur); cur = ''; } else if (c === '\n') { row.push(cur); rows.push(row); row = []; cur = ''; } else if (c !== '\r') cur += c;
  }
  if (cur !== '' || row.length) { row.push(cur); rows.push(row); } return rows.filter(r => r.some(x => x !== ''));
}

/* ---------- model binding for form inputs ---------- */
function setPath(root, path, val) { const ks = path.split('.'); let o = root; for (let i = 0; i < ks.length - 1; i++) { if (o[ks[i]] == null) o[ks[i]] = {}; o = o[ks[i]]; } o[ks[ks.length - 1]] = val; }
function getPath(root, path) { return path.split('.').reduce((o, k) => o == null ? o : o[k], root); }

/* ---------- shared UI snippets ---------- */
const ICON = {
  dash: '<path d="M3 13h8V3H3zM13 21h8V11h-8zM3 21h8v-6H3zM13 3v6h8V3z"/>', list: '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>', bank: '<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V3H6.5A2.5 2.5 0 0 0 4 5.5zM4 19.5V21h16"/>',
  live: '<circle cx="12" cy="12" r="3"/><path d="M7.8 7.8a6 6 0 0 0 0 8.4M16.2 7.8a6 6 0 0 1 0 8.4M4.9 4.9a10 10 0 0 0 0 14.2M19.1 4.9a10 10 0 0 1 0 14.2"/>', res: '<path d="M3 3v18h18M7 15l4-4 3 3 5-6"/>', stu: '<path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM21 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
  exp: '<path d="M12 3v12m0 0l-4-4m4 4l4-4M4 17v3h16v-3"/>', link: '<path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/>', cog: '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
  home: '<path d="M3 11l9-8 9 8M5 10v10h14V10"/>', hist: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8M3 3v5h5M12 7v5l3 2"/>'
};
const ico = k => `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${ICON[k]}</svg>`;
const LOGO = `<svg width="34" height="34" viewBox="0 0 40 40" aria-hidden="true"><rect width="40" height="40" rx="10" fill="#5420A8"/><path d="M12 28V12h8a5 5 0 0 1 2.6 9.3A5.2 5.2 0 0 1 21 28zm4-9h3.6a1.8 1.8 0 0 0 0-3.6H16zm0 5.4h4.2a1.9 1.9 0 0 0 0-3.8H16z" fill="#fff"/><circle cx="29.5" cy="13" r="3.2" fill="#14B8A6"/></svg>`;
function bars(rows, opt = {}) {
  return `<div class="bars">${rows.map(r => `<div class="bar-row"><span class="lab" title="${esc(r.label)}">${esc(r.label)}</span><div class="track" role="img" aria-label="${esc(r.label)} ${r.text || pct(r.value)}"><div class="fill ${opt.teal ? 'teal' : ''}" style="width:${Math.max(0, Math.min(100, (r.value || 0) * 100))}%"></div></div><span class="val">${r.text || pct(r.value)}</span></div>`).join('')}</div>`;
}
function levelStack(counts) {
  const tot = sumBy(counts, x => x) || 1;
  return `<div class="stackbar" role="img" aria-label="${counts.map((c, i) => levels()[i].name + ' ' + c).join(', ')}">${counts.map((c, i) => c ? `<i style="width:${c / tot * 100}%;background:var(--lv${i})"></i>` : '').join('')}</div>`;
}
function levelLegend() { return `<div class="legend">${levels().map((l, i) => `<span><i style="background:var(--lv${i})"></i>${esc(l.name)}</span>`).join('')}</div>`; }
const optList = (list, sel, blank) => (blank != null ? `<option value="">${esc(blank)}</option>` : '') + list.map(x => { const v = typeof x === 'object' ? x.v : x, l = typeof x === 'object' ? x.l : x; return `<option value="${esc(v)}" ${String(v) === String(sel) ? 'selected' : ''}>${esc(l)}</option>`; }).join('');
const field = (label, inner, cls = '') => `<label class="f ${cls}">${esc(label)}${inner}</label>`;
const inp = (path, val, type = 'text', extra = '') => `<input type="${type}" data-m="${path}" ${type === 'number' ? 'data-t="num"' : ''} value="${esc(val)}" ${extra}>`;
const chk = (path, val, label, rerender) => `<label class="chk"><input type="checkbox" data-m="${path}" ${rerender ? 'data-r' : ''} ${val ? 'checked' : ''}><span>${label}</span></label>`;
function topicMeta(subject, topic) { const bankQ = S.bank.filter(q => q.subject === subject && q.topic === topic); const c = { S: 0, H: 0, E: 0, C: 0, M: 0, X: 0 }; bankQ.forEach(q => { if (q.status === 'approved') c[q.diff]++; }); return c; }
function scrollTop() { window.scrollTo(0, 0); }

/* ---------- report sharing (who sees what) ---------- */
const SHARE_SECTIONS = [['summary', 'Score, accuracy and completion'], ['timing', 'Time taken and average time per question'], ['difficulty', 'Difficulty-wise performance and question path'], ['topics', 'Topic mastery levels and evidence'], ['strengths', 'Strengths'], ['gaps', 'Learning gaps and prerequisite notes'],
  ['errors', 'Error pattern analysis'], ['placement', 'Placement recommendation'], ['plan', 'Learning plan, revision topics and extra assessment'], ['questions', 'Question-by-question review with answers'], ['notes', 'Teacher notes'], ['integrity', 'Session record: supervision, accommodations, interruptions'], ['trace', 'Adaptive reasoning log']];
const AUD_NAME = { admin: 'Administrator', teacher: 'Teacher', parent: 'Parent', student: 'Student' };
const DEFAULT_SHARING = {
  teacher: { summary: 1, timing: 1, difficulty: 1, topics: 1, strengths: 1, gaps: 1, errors: 1, placement: 1, plan: 1, questions: 1, notes: 1, integrity: 1, trace: 1 },
  parent: { summary: 1, timing: 0, difficulty: 1, topics: 1, strengths: 1, gaps: 1, errors: 0, placement: 1, plan: 1, questions: 0, notes: 1, integrity: 0, trace: 0 },
  student: { summary: 1, timing: 0, difficulty: 1, topics: 1, strengths: 1, gaps: 1, errors: 0, placement: 1, plan: 1, questions: 1, notes: 1, integrity: 0, trace: 0 }
};
function sharingFor(aud) {
  if (!S.settings.sharing) S.settings.sharing = JSON.parse(JSON.stringify(DEFAULT_SHARING));
  return S.settings.sharing[aud] || DEFAULT_SHARING[aud] || DEFAULT_SHARING.teacher;
}
function addSampleCheck(D) {
  if (D.assessments.some(a => a.id === 'asm_g5check')) return;
  const g5 = D.students.filter(s => s.grade === 5); if (!g5.length) return;
  const a = newAsmt({ id: 'asm_g5check', title: 'Grade 5 Mathematics Check: Strengths and Support', purpose: 'Diagnostic', application: 'school', grades: [5], topics: mathTopics(MATH_TOPICS), model: 'fixed', dist: { S: 8, H: 8, E: 8 }, durationMin: 45, parentSupervision: true,
    outcomes: 'Show which Grade 5 maths topics the student has mastered, which need support, and which earlier skills may be missing.', instructions: 'This check helps your teacher see what you already know and where you need support. It is not a pass or fail test.\nWork on your own. Do not use a calculator, AI tools or other people. If you are unsure, make your best attempt or skip the question.',
    status: 'published', assignedTo: g5.map(s => s.id), resultVis: 'instant', feedback: 'instant', created: Date.now() - 86400000 });
  D.assessments.unshift(a);
  const st = g5.find(s => s.name === 'Fatima Noor') || g5[0];
  const at = simulateAttempt(a, D.bank, st, { theta: 0.25, byTopic: { Addition: 0.8, Subtraction: 0.3, Fractions: 0.9, Division: -1.3, Decimals: -0.9 }, speed: 1, skipP: 0.02 }, 'g5check-' + st.id, Date.now() - 2 * 86400000);
  at.released = true; at.review = { notes: 'Fatima is secure in addition and fractions. Please revisit division facts and decimal place value in class before the next unit.', override: '', finalized: true };
  D.attempts.push(at); D.supervision = D.supervision || {}; D.supervision[a.id + '|' + st.id] = { confirmed: true, by: 'Parent of Fatima', at: Date.now() - 2.1 * 86400000 };
  D.sampleCheckAdded = true;
}
function addStretchSample(D) {
  if (D.assessments.some(a => a.id === 'asm_g5stretch')) return;
  const g5 = D.students.filter(s => s.grade === 5); if (!g5.length) return;
  const a = newAsmt({ id: 'asm_g5stretch', title: 'Grade 5 Maths Challenge: Beyond the Grade', purpose: 'Diagnostic', application: 'school', grades: [5], ageGroup: '9–11 years', stretch: 2, level: 'Mixed',
    topics: mathTopics(['Addition', 'Subtraction', 'Multiplication', 'Division', 'Fractions', 'Decimals']), model: 'adaptive', adaptive: { min: 12, max: 24, minPer: 3, sdTarget: 0.7, startB: -0.5, maxStep: 1.2, probe: true }, durationMin: 60, parentSupervision: true,
    outcomes: 'Find each Grade 5 student’s strongest and weakest maths topics, and show who is ready for harder work beyond the grade.',
    instructions: 'This test starts at a comfortable level and gets harder as you answer well. Some questions are long or tricky on purpose, and some are above Grade 5. You are not expected to get them all right.\nWork on your own. Do not use a calculator, AI tools or other people.',
    status: 'published', assignedTo: g5.map(s => s.id), resultVis: 'release', feedback: 'release', created: Date.now() - 3600000 });
  D.assessments.unshift(a);
  const st = g5.find(s => s.name === 'Ayesha Khan') || g5[0];
  const at = simulateAttempt(a, D.bank, st, { theta: 1.6, byTopic: { Addition: 2.0, Subtraction: 1.7, Multiplication: 1.8, Division: -0.3, Fractions: 2.1, Decimals: 1.0 }, speed: 1, skipP: 0 }, 'g5stretch-' + st.id, Date.now() - 86400000);
  at.released = true; at.review = { notes: 'Ayesha works well above Grade 5 in most topics. Give her multi-step and longer word problems, and check her division method.', override: '', finalized: true };
  D.attempts.push(at); D.supervision = D.supervision || {}; D.supervision[a.id + '|' + st.id] = { confirmed: true, by: 'Parent of ' + st.name, at: Date.now() - 86500000 };
  D.sampleStretchAdded = true;
}
function seed() { const d = seedBase(); d.settings.sharing = JSON.parse(JSON.stringify(DEFAULT_SHARING)); addSampleCheck(d); addStretchSample(d); return d; }
function ensureSample() {
  // The Sheet's own "settings" tab can come back empty (nothing saved
  // there yet) - that must never mean "erase S.settings", since every
  // report depends on it (proficiency levels, sharing, voice, rewards).
  // These guards repair it from scratch if it's ever missing, instead
  // of letting the rest of this function throw on a null read.
  if (!S.settings) S.settings = defaultSettings();
  if (!S.simpleSets) S.simpleSets = [];
  if (!S.settings.assessmentMode) S.settings.assessmentMode = 'old';
  if (!S.settings.levels || !S.settings.levels.length) S.settings.levels = DEFAULT_LEVELS.map(l => ({ ...l }));
  if (!S.settings.sharing) S.settings.sharing = JSON.parse(JSON.stringify(DEFAULT_SHARING));
  // upgrade older saved data: three levels above Advanced, and the matching question tiers
  const lv = S.settings.levels, old5 = lv.length === 5 && lv.every((l, i) => l.name === DEFAULT_LEVELS[i].name && l.min === DEFAULT_LEVELS[i].min);
  if (old5) S.settings.levels = DEFAULT_LEVELS.map(l => ({ ...l }));
  if (!S.stretchBank) { const have = new Set(S.bank.map(q => q.id)); buildBank().forEach(q => { if (STRETCH_KEYS.includes(q.diff) && !have.has(q.id)) S.bank.push(q); }); S.stretchBank = true; }
  if (!S.sampleStretchAdded && S.students.length) addStretchSample(S);
  if (!S.settings.voice) S.settings.voice = Object.assign({}, VOICE_DEFAULT);
  if (!S.settings.rewards) S.settings.rewards = JSON.parse(JSON.stringify(RW_DEFAULT));
  if (!S.sampleCheckAdded && S.students.length) addSampleCheck(S);
  if (!S.samplePathways && S.students.length) addSamplePathways(S);
  save();
}
