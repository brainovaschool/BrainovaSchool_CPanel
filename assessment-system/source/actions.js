/* ===================== ACTIONS, EVENTS, INIT ===================== */
function setModel(el) {
  const m = el.dataset.m; let v = el.type === 'checkbox' ? el.checked : el.value;
  if (el.dataset.t === 'num') v = v === '' ? '' : Number(v);
  const root = m.split('.')[0], parts = m.split('.');
  if (el.dataset.att) { const att = attById(el.dataset.att); if (att) { att.review[parts[1]] = v; anCache = new WeakMap(); save(); } return; }
  if (root === 'lms') { S.settings.lms[parts[1]] = v; save(); return; }
  if (root === 'lvl') { if (!U.lvDraft) U.lvDraft = JSON.parse(JSON.stringify(levels())); U.lvDraft[+parts[1]][parts[2]] = parts[2] === 'min' ? Number(v) : v; return; }
  if (root === 'stuAcc') { const s = studentById(U.stuSel); if (!s) return; s.accom = s.accom || { extraPct: 0, largeText: false }; if (parts[1] === 'extra') s.accom.extraPct = Number(v); else s.accom.largeText = !!v; save(); return; }
  if (root === 'stuGrade') { const s = studentById(U.stuSel); if (!s) return; s.grade = v === '' ? null : Number(v); save(); return; }
  setPath(U, m, v);
}
const ACT = {};
document.addEventListener('click', e => {
  const el = e.target.closest('[data-a]'); if (!el) return; const t = el.tagName, ty = el.type;
  if (t === 'SELECT' || t === 'TEXTAREA' || (t === 'INPUT' && ty !== 'checkbox' && ty !== 'radio')) return;
  const fn = ACT[el.dataset.a]; if (!fn) return;
  if (t === 'BUTTON' || t === 'A') e.preventDefault();
  fn(el, e);
});
document.addEventListener('change', e => {
  const el = e.target;
  if (el.dataset.m) { setModel(el); if (el.dataset.r !== undefined && !el.dataset.live) render(); return; }
  if (el.dataset.a && (el.tagName === 'SELECT' || ty(el) === 'file')) { const fn = ACT[el.dataset.a]; if (fn) fn(el, e); }
});
function ty(el) { return el.type; }
document.addEventListener('input', e => {
  const el = e.target;
  if (el.dataset.a === 'typed') { ACT.typed(el); return; }
  if (el.dataset.m && el.type !== 'radio' && el.tagName !== 'SELECT') {
    setModel(el);
    if (el.dataset.live !== undefined) { const id = el.id, pos = el.selectionStart; render(); const n = document.getElementById(id); if (n) { n.focus(); try { n.setSelectionRange(pos, pos); } catch (x) { } } }
  }
});

/* navigation */
ACT.go = el => go(el.dataset.v);
ACT.role = el => setRole(el.dataset.v);
ACT.openAtt = el => { U.repAud = null; go('report', { id: el.dataset.id }); };
ACT.repAud = el => { U.repAud = el.dataset.v; render(); };
ACT.clearFilters = () => { U.filters = { range: 'all', subject: '', grade: '', course: '', asmt: '', topic: '', level: '', student: '' }; render(); };
ACT.kid = el => { U.params.kid = el.dataset.id; render(); };
ACT.parOpen = el => { U.parOpen = el.dataset.id; U.supTick = []; render(); };

/* assessments and builder */
function persistDraft() { const d = U.draft, i = S.assessments.findIndex(a => a.id === d.id); if (i >= 0) S.assessments[i] = JSON.parse(JSON.stringify(d)); else S.assessments.unshift(JSON.parse(JSON.stringify(d))); save(); }
ACT.newAsmt = () => { U.draft = newAsmt(); U.step = 1; U.pvSeed = 1; go('builder'); };
ACT.editAsmt = el => { U.draft = JSON.parse(JSON.stringify(asmtById(el.dataset.id))); U.step = 1; go('builder'); };
ACT.dupAsmt = el => { const c = JSON.parse(JSON.stringify(asmtById(el.dataset.id))); c.id = uid('asm'); c.title += ' (copy)'; c.status = 'draft'; c.assignedTo = []; c.leadIntake = false; c.created = Date.now(); S.assessments.unshift(c); save(); render(); toast('Duplicated as a draft. "Offer on the public booking page" was turned off on the copy - turn it back on once you\'re sure only one should be live for this grade and subject.'); };
ACT.asgOpen = el => { U.asgOpen = U.asgOpen === el.dataset.id ? null : el.dataset.id; render(); };
ACT.asgTgl = el => { const a = asmtById(el.dataset.id).assignedTo, i = a.indexOf(el.dataset.sid); if (i >= 0) a.splice(i, 1); else a.push(el.dataset.sid); save(); render(); };
ACT.asgAll = el => { const a = asmtById(el.dataset.id); a.assignedTo = S.students.filter(s => !a.grades.length || a.grades.includes(s.grade)).map(s => s.id); save(); render(); };
ACT.asgNone = el => { asmtById(el.dataset.id).assignedTo = []; save(); render(); };
ACT.asgAttempts = el => { asmtById(el.dataset.id).attempts = +el.value; save(); render(); toast('Attempts allowed: ' + el.value); };
ACT.closeAsmt = el => { asmtById(el.dataset.id).status = 'closed'; save(); render(); };
ACT.reopenAsmt = el => { asmtById(el.dataset.id).status = 'published'; save(); render(); };
ACT.asmtResults = el => { U.resTab = 'class'; go('results', { asmt: el.dataset.id }); };
ACT.liveSetup = el => { U.liveSetup = { asmtId: el.dataset.id, ids: null }; go('live'); };
ACT.step = el => { U.step = +el.dataset.n; render(); scrollTop(); };
ACT.tglGrade = el => { const g = +el.dataset.g, a = U.draft.grades, i = a.indexOf(g); if (i >= 0) a.splice(i, 1); else a.push(g); a.sort((x, y) => x - y); render(); };
ACT.tglSyllabus = el => { const s = el.dataset.s, a = U.draft.syllabus = U.draft.syllabus || [], i = a.indexOf(s); if (i >= 0) a.splice(i, 1); else a.push(s); render(); };
ACT.useCourseTopics = () => { const c = courseOf(U.draft); if (!c) return; U.draft.topics = c.topics.map(t => ({ subject: c.subject, topic: t })); U.draft._subs = [c.subject]; if (!U.draft.title) U.draft.title = c.name + ' placement'; render(); toast('Course topics selected.'); };
function syl(s) { return SYLLABUS.find(x => x.subject === s); }
ACT.tglSubject = el => { const s = el.dataset.s, d = U.draft, act = new Set(d.topics.map(t => t.subject).concat(d._subs || []));
  if (act.has(s)) { d.topics = d.topics.filter(t => t.subject !== s); d._subs = (d._subs || []).filter(x => x !== s); } else d._subs = (d._subs || []).concat([s]); render(); };
ACT.selSubject = el => { const s = el.dataset.s, d = U.draft; d.topics = d.topics.filter(t => t.subject !== s).concat(syl(s).units.flatMap(u => u.topics).map(t => ({ subject: s, topic: t }))); render(); };
ACT.clrSubject = el => { const s = el.dataset.s, d = U.draft; d.topics = d.topics.filter(t => t.subject !== s); render(); };
ACT.tglUnit = el => { const s = el.dataset.s, u = syl(s).units.find(x => x.unit === el.dataset.u), d = U.draft, all = u.topics.every(t => selectedTopic(d, s, t));
  d.topics = d.topics.filter(t => !(t.subject === s && u.topics.includes(t.topic))); if (!all) u.topics.forEach(t => d.topics.push({ subject: s, topic: t })); render(); };
ACT.tglTopic = el => { const s = el.dataset.s, t = el.dataset.t, d = U.draft; if (selectedTopic(d, s, t)) d.topics = d.topics.filter(x => !(x.subject === s && x.topic === t)); else d.topics.push({ subject: s, topic: t }); render(); };
ACT.tglObj = el => { const k = el.dataset.k, a = U.draft.excludeObj = U.draft.excludeObj || [], i = a.indexOf(k); if (i >= 0) a.splice(i, 1); else a.push(k); render(); };
ACT.tglPinnedQ = el => { const id = el.dataset.id, a = U.draft.pinnedIds = U.draft.pinnedIds || [], i = a.indexOf(id); if (i >= 0) a.splice(i, 1); else a.push(id); render(); };
ACT.splitDist = el => { U.draft.dist = { S: +el.dataset.s, H: +el.dataset.h, E: +el.dataset.e }; render(); };
ACT.redraw = () => { U.pvSeed = (U.pvSeed || 1) + 1; render(); };
const candIds = () => S.students.filter(s => !U.draft.grades.length || U.draft.grades.includes(s.grade)).map(s => s.id);
ACT.assignAll = () => { U.draft.assignedTo = candIds(); render(); };
ACT.assignNone = () => { U.draft.assignedTo = []; render(); };
ACT.tglAssign = el => { const a = U.draft.assignedTo, i = a.indexOf(el.dataset.id); if (i >= 0) a.splice(i, 1); else a.push(el.dataset.id); render(); };
ACT.saveDraft = () => { if (!U.draft.title.trim()) U.draft.title = 'Untitled assessment'; persistDraft(); toast('Draft saved.'); render(); };
ACT.previewRun = () => { const d = U.draft, st = S.students.find(s => d.grades.includes(s.grade)) || S.students[0]; U.previewFrom = 'builder'; startRun(d, st.id, true); };
ACT.previewAsmt = el => { const a = asmtById(el.dataset.id); if (!a) return; const st = S.students.find(s => a.grades.includes(s.grade)) || S.students[0]; U.previewFrom = 'assess'; startRun(a, st.id, true); };
ACT.publish = () => { const v = validateDraft(U.draft); if (v.err.length) { toast(v.err[0]); return; } U.draft.status = 'published'; persistDraft(); toast('Published. Assigned students can see it now.'); go('assess'); };

/* simple assessment procedure */
ACT.setMode = el => { S.settings.assessmentMode = el.dataset.v; save(); render();
  toast(el.dataset.v === 'simple' ? 'Switched to the simple CSV system. Any old assessment still offered on the public page has been suspended for now - open each simple set and turn its own "offer on the public booking page" on when it is ready.' : 'Switched back to the original Builder system - any suspended assessment is offered again exactly as it was.'); };
ACT.simpleNew = () => { U.simpleDraft = { _origId: null, grade: 1, subject: 'Mathematics', title: '', questionCount: 20, leadIntake: false, questions: [] }; U.simpleErr = null; render(); scrollTop(); };
ACT.simpleEdit = el => { const s = S.simpleSets.find(x => x.id === el.dataset.id); if (!s) return; U.simpleDraft = Object.assign(JSON.parse(JSON.stringify(s)), { _origId: s.id }); U.simpleErr = null; render(); scrollTop(); };
ACT.simpleClose = () => { U.simpleDraft = null; render(); };
ACT.simpleTglQ = el => { const q = (U.simpleDraft.questions || []).find(x => x.id === el.dataset.id); if (q) q.included = q.included === false; render(); };
ACT.simpleSelAll = () => { (U.simpleDraft.questions || []).forEach(q => q.included = true); render(); };
ACT.simpleSelNone = () => { (U.simpleDraft.questions || []).forEach(q => q.included = false); render(); };
ACT.simpleDelete = el => {
  const key = 'simpledel-' + el.dataset.id;
  if (U.cfm !== key) { U.cfm = key; render(); return; }
  U.cfm = null;
  S.simpleSets = S.simpleSets.filter(s => s.id !== el.dataset.id);
  S.assessments = S.assessments.filter(a => a.simpleSetId !== el.dataset.id);
  save(); render(); toast('Simple set deleted.');
};
ACT.simpleTpl = () => saveFile('brainova-simple-set-template.csv', toCSV([
  ['difficulty', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'answer', 'explanation'],
  ['Hard', 'What is 23 + 48?', '71', '70', '81', '61', 'a', '23 + 48 = 71.'],
  ['Strengthen', 'What is 5 + 3?', '7', '8', '9', '6', 'b', ''],
  ['Extend', 'A shop had 128 pens and sold 47. How many are left?', '81', '79', '91', '71', 'a', '128 - 47 = 81.'],
]));
ACT.simpleImportCsv = async el => {
  const f = el.files[0]; if (!f) return;
  try {
    const buf = await f.arrayBuffer();
    let text; try { text = new TextDecoder('utf-8', { fatal: true }).decode(buf); } catch (e) { text = new TextDecoder('windows-1252').decode(buf); }
    const rows = parseCSV(text), h = rows[0].map(x => x.trim().toLowerCase()), ix = k => h.indexOf(k);
    const g = (c, k) => ix(k) >= 0 ? (c[ix(k)] || '').trim() : '';
    const tierOf = s => { const t = s.trim().toLowerCase(); if (t[0] === 'h') return 'H'; if (t[0] === 's') return 'S'; if (t[0] === 'e') return 'E'; return null; };
    const qs = []; let bad = 0;
    let skippedTier = 0;
    rows.slice(1).forEach(c => {
      const diffRaw = g(c, 'difficulty') || '';
      const diff = tierOf(diffRaw);
      // Accepts either this system's own simple template ("question")
      // or the Question bank's own richer CSV shape ("text"), so a
      // file exported/built for the other system doesn't need
      // reformatting just to try this one.
      const text = g(c, 'question') || g(c, 'text');
      const type = (g(c, 'type') || 'mcq').toLowerCase();
      let opts, ansIdx;
      if (type === 'tf') {
        opts = ['True', 'False'];
        const ansRaw = g(c, 'answer').toLowerCase();
        ansIdx = ansRaw[0] === 't' ? 0 : ansRaw[0] === 'f' ? 1 : -1;
      } else {
        opts = ['option_a', 'option_b', 'option_c', 'option_d'].map(k => g(c, k));
        const ansRaw = g(c, 'answer').toLowerCase();
        ansIdx = 'abcd'.indexOf(ansRaw);
        if (ansIdx < 0) ansIdx = opts.findIndex(o => o.toLowerCase() === ansRaw);
      }
      if (diffRaw && !diff) { skippedTier++; return; } // a real difficulty this system just doesn't use (e.g. Challenging/Mastery/Exceptional) - not an error in the row
      if (!diff || !text || opts.filter(Boolean).length < 2 || ansIdx < 0 || !opts[ansIdx]) { bad++; return; }
      // Optional - a plain simple-template CSV has no 'topic' column at
      // all (g() just returns '' for it, same as always), but a file
      // built from the Question bank's own richer CSV shape often does
      // have real topic names already filled in, and there's no reason
      // to throw that away just because it came in through this
      // importer. engine.js falls back to 'General' when this is blank,
      // same behaviour as before this existed.
      qs.push({ id: 'simpleq-' + uid('').slice(1, 9), diff, text, options: opts.map((t, j) => ({ id: 'abcd'[j], t })).filter(o => o.t), answer: 'abcd'[ansIdx], explanation: g(c, 'explanation') || '', topic: g(c, 'topic') || '' });
    });
    U.simpleDraft.questions = qs;
    // A fresh CSV means this course's content is being replaced, not
    // tweaked - any students already assigned under the OLD content
    // should not carry over as if they'd been assigned the new one.
    // Harmless to set unconditionally: a brand-new (unsaved) set has no
    // existing assignments to forget in the first place.
    U.simpleDraft._contentReplaced = true;
    const notes = [];
    if (bad) notes.push(`${bad} skipped - missing difficulty, question text, at least 2 options, or an answer that doesn't match an option`);
    if (skippedTier) notes.push(`${skippedTier} skipped - tagged with a difficulty this simple system doesn't use (only Hard, Strengthen and Extend)`);
    render(); toast(`Loaded ${qs.length} question${qs.length === 1 ? '' : 's'}${notes.length ? ` (${notes.join('; ')}).` : '.'}`);
  } catch (err) { toast('Could not read that CSV file.'); }
  el.value = '';
};
ACT.simpleSave = () => {
  const d = U.simpleDraft;
  if (!d.questions.length) { U.simpleErr = 'Upload a CSV of questions first.'; render(); return; }
  const id = simpleSetKey(d.grade, d.subject);
  S.simpleSets = S.simpleSets.filter(s => s.id !== id && s.id !== d._origId);
  if (d._origId && d._origId !== id) S.assessments = S.assessments.filter(a => a.simpleSetId !== d._origId);
  const saved = { id, grade: +d.grade, subject: d.subject, title: (d.title || '').trim(), questionCount: Math.max(1, +d.questionCount || 1), leadIntake: !!d.leadIntake, questions: d.questions, updated: Date.now() };
  S.simpleSets.unshift(saved);
  syncSimpleCompanion(saved, !!d._contentReplaced);
  save(); U.simpleDraft = null; U.simpleErr = null; render(); toast('Simple set saved.');
};

/* question bank */
const blankQ = () => ({ id: 'Q-' + uid('').slice(1).toUpperCase(), subject: 'Mathematics', topic: 'Addition', unit: null, grades: [3, 5], diff: 'S', type: 'mcq', text: '', objective: '', explanation: '', marking: 'Full marks for the correct answer.', marks: 1, time: 45, prereq: null, source: 'Teacher', status: 'approved', options: [{ id: 'a', t: '' }, { id: 'b', t: '' }, { id: 'c', t: '' }, { id: 'd', t: '' }], answer: 'a' });
ACT.qNew = () => { U.qDraft = qToForm(blankQ()); U.qErr = null; U.qGen = null; render(); scrollTop(); };
ACT.qEdit = el => { const q = S.bank.find(x => x.id === el.dataset.id); U.qDraft = qToForm(q); U.qDraft.origDiff = q.diff; U.qErr = null; U.qGen = null; render(); scrollTop(); };
ACT.qClose = () => { U.qDraft = null; U.qErr = null; render(); };
ACT.qSave = () => { const q = formToQ(U.qDraft), p = qProblems(q); if (p.length) { U.qErr = p; render(); return; } const i = S.bank.findIndex(x => x.id === q.id); if (i >= 0) S.bank[i] = q; else S.bank.unshift(q); U.qDraft = null; U.qErr = null; save(); render(); toast('Question saved.'); };
ACT.qDup = () => { const f = U.qDraft; f.id = 'Q-' + uid('').slice(1).toUpperCase(); f.origDiff = null; ACT.qSave(); };
ACT.qDel = () => { if (U.cfm !== 'qdel') { U.cfm = 'qdel'; toast('Click Delete again to remove this question.'); return; } U.cfm = null; S.bank = S.bank.filter(x => x.id !== U.qDraft.id); U.qDraft = null; save(); render(); toast('Question deleted.'); };
ACT.qImg = el => { const f = el.files[0]; if (!f) return; if (f.size > 300 * 1024) { toast('Image is larger than 300 KB. Use a smaller file.'); return; } const r = new FileReader(); r.onload = () => { U.qDraft.image = r.result; render(); }; r.readAsDataURL(f); };
ACT.qImgClear = () => { U.qDraft.image = null; render(); };
ACT.approveAll = () => { S.bank.forEach(q => q.status = 'approved'); save(); render(); toast('All drafts approved.'); };
ACT.bankMore = () => { U.bankLimit = (U.bankLimit || 25) + 50; render(); };
ACT.bankTgl = el => { const sel = U.bankSel = U.bankSel || [], i = sel.indexOf(el.dataset.id); if (i >= 0) sel.splice(i, 1); else sel.push(el.dataset.id); render(); };
ACT.bankSelAll = () => { U.bankSel = filteredBank().map(q => q.id); render(); };
ACT.bankSelNone = () => { U.bankSel = []; render(); };
ACT.bankBulkDelete = () => {
  if (U.cfm !== 'bankdel') { U.cfm = 'bankdel'; render(); return; }
  U.cfm = null;
  const ids = new Set(U.bankSel || []);
  S.bank = S.bank.filter(q => !ids.has(q.id));
  U.bankSel = [];
  save(); render(); toast(`${ids.size} question${ids.size === 1 ? '' : 's'} deleted.`);
};
ACT.exportBank = () => saveFile('brainova-question-bank.csv', EXPORTS.bank());
ACT.scanMojibake = () => { const fixes = scanMojibake(); U.mojibakeFixes = fixes; render(); if (!fixes.length) toast('No broken text found.'); };
ACT.applyMojibake = () => { const fixes = U.mojibakeFixes || []; applyMojibakeFixes(fixes); U.mojibakeFixes = null; save(); render(); toast(`Fixed broken text in ${new Set(fixes.map(f => f.id)).size} question${new Set(fixes.map(f => f.id)).size === 1 ? '' : 's'}.`); };
ACT.cancelMojibake = () => { U.mojibakeFixes = null; render(); };
ACT.tplBank = () => saveFile('brainova-question-import-template.csv', toCSV([['subject', 'topic', 'grade_min', 'grade_max', 'difficulty', 'type', 'text', 'option_a', 'option_b', 'option_c', 'option_d', 'answer', 'explanation', 'objective', 'time_sec', 'marks', 'prerequisite', 'source'], ['Mathematics', 'Addition', 2, 4, 'S', 'mcq', 'What is 24 + 13?', '35', '37', '36', '38', 'b', '24 + 13 = 37', 'Add two-digit numbers', 30, 1, '', 'Teacher'], ['English', 'Tenses', 4, 6, 'S', 'fill', 'Yesterday I ____ (go) to school.', '', '', '', '', 'went', '', 'Use simple past', 30, 1, '', 'Teacher']]));
ACT.importQ = async el => {
  const f = el.files[0]; if (!f) return;
  try {
    const buf = await f.arrayBuffer();
    // Excel's plain "CSV" export (as opposed to "CSV UTF-8") saves
    // using the system's default code page, not UTF-8 - reading that
    // as UTF-8 corrupts every ×, ÷, − and other non-ASCII character
    // into mojibake. Decoding strictly (fatal: true) means an invalid
    // UTF-8 byte sequence - the signature of a non-UTF-8 file - throws
    // here instead of silently mangling the text, so it falls through
    // to the Windows-1252 fallback instead.
    let text;
    try { text = new TextDecoder('utf-8', { fatal: true }).decode(buf); }
    catch (e) { text = new TextDecoder('windows-1252').decode(buf); }
    const rows = parseCSV(text), h = rows[0].map(x => x.trim().toLowerCase()), ix = k => h.indexOf(k); let ok = 0, bad = 0, noGrade = 0;
    rows.slice(1).forEach(c => { const g = k => ix(k) >= 0 ? (c[ix(k)] || '').trim() : ''; const type = g('type') || 'mcq', diff = (g('difficulty') || 'S').toUpperCase()[0];
      const f2 = qToForm(Object.assign(blankQ(), { subject: g('subject'), topic: g('topic'), diff: DIFF_KEYS.includes(diff) ? diff : 'S', type, text: g('text'), objective: g('objective'), explanation: g('explanation'), marks: +g('marks') || 1, time: +g('time_sec') || 45, prereq: g('prerequisite') || null, source: g('source') || 'Imported', status: 'draft' }));
      // Recognise several column-naming styles, including a single
      // "grade"/"class" column with one number or a range like "1-3".
      // A row with no recognisable grade at all is left blank (not
      // defaulted to Grades 1-10) - gradeOk() then never matches it to
      // a real assessment grade, so it simply can't be used until someone
      // sets Grade from/to by hand.
      let g0 = +(g('grade_min') || g('min_grade') || g('grade_from')) || 0, g1 = +(g('grade_max') || g('max_grade') || g('grade_to')) || 0;
      const gs = (g('grade') || g('class') || g('grades')).match(/\d+/g); if (!g0 && !g1 && gs) { g0 = Math.min(...gs.map(Number)); g1 = Math.max(...gs.map(Number)); }
      if (g0 && !g1) g1 = g0; if (g1 && !g0) g0 = g1;
      if (!g0) noGrade++; f2._g0 = g0 || ''; f2._g1 = g1 || ''; f2._opts = ['option_a', 'option_b', 'option_c', 'option_d'].map(k => g(k)); const a = g('answer');
      if (type === 'mcq') f2._correct = Math.max(0, 'abcd'.indexOf(a.toLowerCase())); if (type === 'tf') f2._tf = /^t/i.test(a) ? 't' : 'f'; if (type === 'num') f2._num = a; if (type === 'fill') f2._accept = a.replace(/\|/g, ' | '); if (type === 'short') f2._kw = a.replace(/\|/g, ' | ');
      f2.id = 'Q-' + uid('').slice(1).toUpperCase(); const q = formToQ(f2); if (qProblems(q).filter(x => !/grade/i.test(x)).length) bad++; else { S.bank.unshift(q); ok++; } });
    save(); U.bankFilter.status = 'draft'; render(); toast(`Imported ${ok} question${ok === 1 ? '' : 's'} as drafts${bad ? ` (${bad} skipped because of missing fields)` : ''}.${noGrade ? ` ${noGrade} have no grade, so they will not appear in any test until you set Grade from and Grade to.` : ''}`);
  } catch (err) { toast('Could not read that CSV file.'); }
  el.value = '';
};
ACT.qGenOpen = () => { U.qGen = { method: 'rule', topic: 'Fractions', subject: 'Science', diff: 'S', count: 3, grade: 5, notes: '' }; U.qDraft = null; U.qGenMsg = ''; render(); scrollTop(); };
ACT.qGenClose = () => { U.qGen = null; render(); };
ACT.qGenRun = async () => {
  const g = U.qGen, n = Math.max(1, Math.min(10, +g.count || 1)); U.qGenMsg = '';
  if (g.method === 'rule') {
    const have = new Set(S.bank.map(q => q.text)); let added = 0;
    for (let i = 0; added < n && i < 80; i++) { const q = genMathQ(g.topic, g.diff, 'gen' + Date.now() + '-' + i, 90 + added); if (have.has(q.text)) continue; q.id = 'MATH-' + g.topic.slice(0, 3).toUpperCase() + '-' + g.diff + '-G' + uid('').slice(1, 5).toUpperCase(); q.status = 'draft'; q.source = 'Generated variant (rule based)'; have.add(q.text); S.bank.unshift(q); added++; }
    save(); U.bankFilter.status = 'draft'; U.qGenMsg = `${added} draft${added === 1 ? '' : 's'} added. Review them below.`; render(); return;
  }
  const sample = window.claude && await window.claude.use('sample');
  if (!sample) { U.qGenMsg = 'AI generation is not available in this view. Use the rule-based option or import a CSV.'; render(); return; }
  U.qGenBusy = true; render();
  try {
    const prompt = `You write assessment questions for Brainova, a school and short-course provider in Pakistan (Grades 1 to 10).\nWrite ${n} multiple-choice questions.\nSubject: ${g.subject}\nTopic: ${g.topic}\nGrade: ${g.grade}\nDifficulty: ${DIFF[g.diff].name} (Strengthen = foundation recall and basic application; Hard = multi-step reasoning and application; Extend = higher-order thinking with unfamiliar problems).\n${g.notes ? 'Extra guidance: ' + g.notes + '\n' : ''}Rules: exactly 4 distinct options with exactly one correct answer; academically correct; age-appropriate for the grade; clear wording; vary the context; no trick questions.\nReturn only a JSON array. Each item: {"text": string, "options": [4 strings], "answerIndex": 0-3, "explanation": string, "objective": string, "seconds": number}.`;
    const out = await sample.json(prompt); const arr = Array.isArray(out) ? out : (out && out.questions) || []; let added = 0;
    arr.forEach(x => { if (!x || !x.text || !Array.isArray(x.options) || x.options.length !== 4 || new Set(x.options.map(String)).size !== 4 || !(x.answerIndex >= 0 && x.answerIndex <= 3)) return;
      const f = qToForm(Object.assign(blankQ(), { subject: g.subject, topic: g.topic, diff: g.diff, text: String(x.text), objective: String(x.objective || 'AI draft'), explanation: String(x.explanation || ''), time: +x.seconds || 45, source: 'AI draft (needs review)', status: 'draft' }));
      f._g0 = Math.max(1, +g.grade - 1); f._g1 = Math.min(10, +g.grade + 1); f._opts = x.options.map(String); f._correct = +x.answerIndex; f.id = 'AI-' + uid('').slice(1, 7).toUpperCase(); const q = formToQ(f); if (!qProblems(q).length) { S.bank.unshift(q); added++; } });
    save(); U.bankFilter.status = 'draft'; U.qGenMsg = added ? `${added} AI draft${added === 1 ? '' : 's'} added. Check each answer before approving.` : 'The response could not be used. Try again.';
  } catch (e) { U.qGenMsg = e && e.code === 'not_granted' ? 'Permission to use Claude was not granted.' : 'AI generation did not complete: ' + ((e && e.message) || 'unknown error'); }
  U.qGenBusy = false; render();
};
ACT.qGuideOpen = () => { U.qGuide = true; render(); scrollTop(); };
ACT.qGuideClose = () => { U.qGuide = false; render(); };
ACT.dlGuide = () => {
  const sec = (title, head, rows) => [[title], head, ...rows, []];
  const rows = [].concat(
    sec('How to use this file', ['Item', 'Detail'], QGUIDE.how),
    sec('Difficulty levels', ['Letter', 'Level', 'What it tests', 'How to write it', 'Time needed', 'Example', 'Usual grade range'], QGUIDE.levels),
    sec('Column rules', ['Column', 'Needed?', 'Format', 'Rule', 'Example'], QGUIDE.fields),
    sec('Writing wrong options', ['Kind', 'Guidance'], QGUIDE.opts),
    sec('Quality checklist', ['Check', 'What to do'], QGUIDE.check));
  saveFile('brainova-question-writing-guide.csv', toCSV(rows));
};

/* results */
ACT.resTab = el => { U.resTab = el.dataset.v; render(); };
ACT.scoreManual = el => { const att = attById(el.dataset.id), i = +el.dataset.i, r = att.responses[i], q = att.items[i], inpEl = document.getElementById('mm_' + att.id + '_' + i), v = Math.max(0, Math.min(q.max || q.marks, Number(inpEl.value) || 0));
  r.marks = v; r.manualMarks = v; r.status = v >= (q.max || q.marks) * 0.5 ? 'correct' : 'incorrect'; anCache = new WeakMap(); save(); render(); toast('Score saved. The level has been recalculated.'); };
ACT.toggleRelease = el => { const a = attById(el.dataset.id); a.released = !a.released; save(); render(); toast(a.released ? 'Results released.' : 'Results withdrawn.'); };
ACT.finalize = el => { const a = attById(el.dataset.id); a.review.finalized = true; save(); render(); toast('Recommendation finalised and linked to the student profile.'); };
const fileStem = a => a.studentName.replace(/\s+/g, '-').toLowerCase() + '-' + a.id.slice(-4);
const audOf = el => el.dataset.aud || U.repAud || (U.role === 'teacher' || U.role === 'admin' ? U.role : U.role);
ACT.dlReport = el => { const a = attById(el.dataset.id), aud = audOf(el); saveFile(`brainova-report-${fileStem(a)}-${aud}.html`, reportDoc(a, aud)); };
ACT.dlCsv = el => { const a = attById(el.dataset.id), aud = audOf(el); saveFile(`brainova-summary-${fileStem(a)}-${aud}.csv`, reportCSV(a, aud)); };
ACT.dlAll = async el => { const a = attById(el.dataset.id), list = U.role === 'admin' ? ['admin', 'teacher', 'parent'] : ['teacher', 'parent']; for (const aud of list) { await saveFile(`brainova-report-${fileStem(a)}-${aud}.html`, reportDoc(a, aud)); } };
ACT.shareTgl = el => { sharingFor(el.dataset.aud)[el.dataset.k] = el.checked ? 1 : 0; save(); render(); };
ACT.shareReset = () => { S.settings.sharing = JSON.parse(JSON.stringify(DEFAULT_SHARING)); save(); render(); toast('Sharing defaults restored.'); };
ACT.trySample = () => { const a = asmtById('asm_g5check'); if (!a) { toast('The sample test is not in this data set.'); return; } const st = S.students.find(s => a.assignedTo.includes(s.id) && !attemptsOf(a.id, s.id).length && !(s.accom || {}).extraPct) || S.students.find(s => a.assignedTo.includes(s.id));
  U.role = 'student'; U.studentId = st.id; U.supTick = []; U.supName = ''; go('intro', { id: a.id }); };
ACT.dlResp = el => { const a = attById(el.dataset.id); saveFile(`brainova-responses-${a.studentName.replace(/\s+/g, '-').toLowerCase()}.csv`, toCSV([['sequence', 'question_id', 'topic', 'difficulty', 'status', 'student_answer', 'correct_answer', 'marks', 'time_s', 'answer_changed']].concat(a.responses.map(r => [r.seq, r.qid, r.topic, DIFF[r.diff].name, r.status, r.answer, r.correctAnswer, r.marks, (r.timeMs / 1000).toFixed(1), r.changed])))); };
ACT.stuOpen = el => { U.stuSel = el.dataset.id; render(); scrollTop(); };
ACT.stuClose = () => { U.stuSel = null; render(); };
// Removes any one student - the built-in demo ones or a real but
// unwanted LMS entry alike - from this app's own list only. It cannot
// remove their old rows already written to the Google Sheet (there is
// no delete action there, only upsert); harmless leftover rows, not
// something that resurfaces in this app. If the student is still
// active in the LMS, Import from LMS will bring them back - deactivate
// or delete them there first for a removal that sticks.
ACT.stuDelete = el => {
  const id = el.dataset.id;
  if (U.cfm !== 'del:' + id) { U.cfm = 'del:' + id; render(); return; }
  U.cfm = null;
  S.students = S.students.filter(s => s.id !== id);
  S.attempts = S.attempts.filter(a => a.studentId !== id);
  U.stuSel = null;
  anCache = new WeakMap();
  save(); render();
  toast('Removed from this app. Still active in the LMS? Import from LMS will bring them back - deactivate them there first if you don’t want that.');
};

/* bulk student actions (Students page toolbar) */
ACT.stuBulkTgl = el => {
  const id = el.dataset.id, i = (U.stuBulk || (U.stuBulk = [])).indexOf(id);
  if (i >= 0) U.stuBulk.splice(i, 1); else U.stuBulk.push(id);
  render();
};
ACT.stuBulkAll = () => { U.stuBulk = S.students.map(s => s.id); render(); };
ACT.stuBulkNone = () => { U.stuBulk = []; U.cfm = null; render(); };
// Same caveat as the single-student delete above: local only, never
// touches the Google Sheet or the LMS itself.
ACT.stuBulkDelete = () => {
  if (!U.stuBulk || !U.stuBulk.length) return;
  if (U.cfm !== 'bulkdel') { U.cfm = 'bulkdel'; render(); return; }
  U.cfm = null;
  const ids = new Set(U.stuBulk);
  S.students = S.students.filter(s => !ids.has(s.id));
  S.attempts = S.attempts.filter(a => !ids.has(a.studentId));
  const n = U.stuBulk.length;
  U.stuBulk = [];
  anCache = new WeakMap();
  save(); render();
  toast(`Removed ${n} student(s) from this app.`);
};
ACT.stuBulkAssign = el => {
  const asmtId = el.value;
  if (!asmtId || !U.stuBulk || !U.stuBulk.length) { el.value = ''; return; }
  const a = asmtById(asmtId);
  if (!a) { el.value = ''; return; }
  const set = new Set(a.assignedTo);
  U.stuBulk.forEach(id => set.add(id));
  a.assignedTo = [...set];
  const n = U.stuBulk.length;
  save(); render();
  toast(`Assigned ${n} student(s) to "${a.title || 'Untitled'}".`);
};

/* student */
ACT.intro = el => { U.supTick = []; U.supName = ''; go('intro', { id: el.dataset.id }); };
ACT.supTick = el => { U.supTick = U.supTick || []; U.supTick[+el.dataset.i] = el.checked; };
ACT.supConfirm = el => { const ok = SUP_ITEMS.every((x, i) => U.supTick && U.supTick[i]); const name = (U.supName || '').trim() || (S.parents.find(p => p.id === U.parentId) || {}).name || '';
  if (!ok) { toast('Please tick every point to confirm.'); return; } if (!name) { toast('Please enter the parent or guardian name.'); return; }
  S.supervision[supKey(el.dataset.id, el.dataset.s)] = { confirmed: true, by: name, at: Date.now() }; save(); U.parOpen = null; render(); toast('Supervision confirmed.'); };
ACT.beginRun = el => { U.previewFrom = null; startRun(asmtById(el.dataset.id), U.studentId, false); };
ACT.pick = el => { setAnswer(el.dataset.v); render(); };
ACT.typed = el => setAnswer(el.value);
ACT.ord = el => { const r = U.run, e = r.resp[r.idx], cur = Array.isArray(e.raw) ? e.raw.slice() : [], v = el.dataset.v, i = cur.indexOf(v); if (i >= 0) cur.splice(i, 1); else cur.push(v); setAnswer(cur.length ? cur : null); render(); };
ACT.ordClear = () => { setAnswer(null); U.run.resp[U.run.idx].raw = null; render(); };
ACT.mat = el => { const r = U.run, e = r.resp[r.idx], q = curQ(), cur = Array.isArray(e.raw) ? e.raw.slice() : q.pairs.map(() => ''); cur[+el.dataset.i] = el.value; setAnswer(cur); };
ACT.jump = el => { const r = U.run; flushTime(); r.idx = +el.dataset.i; r.resp[r.idx].visits++; r.phase = 'q'; r.qStart = Date.now(); render(); scrollTop(); };
ACT.prevQ = () => { const r = U.run; flushTime(); r.idx = Math.max(0, r.idx - 1); r.resp[r.idx].visits++; r.qStart = Date.now(); render(); scrollTop(); };
ACT.skipQ = () => advance();
ACT.nextQ = () => advance();
ACT.backToQ = () => { const r = U.run; r.phase = 'q'; r.idx = r.items.length - 1; r.qStart = Date.now(); r.resp[r.idx].visits++; render(); };
ACT.submitRun = () => submitRun(false);
ACT.exitRun = () => { U.run = null; try { if (document.fullscreenElement) document.exitFullscreen(); } catch (e) { } go(U.previewFrom === 'builder' ? 'builder' : U.previewFrom === 'assess' ? 'assess' : 'my'); };
ACT.finishRun = el => { const r = U.run, att = r.att, prev = r.preview; U.run = null; try { if (document.fullscreenElement) document.exitFullscreen(); } catch (e) { }
  if (el.dataset.view === 'report') go('report', { id: att.id }); else go(prev ? 'builder' : 'my'); };

/* export / integration / settings */
ACT.exp = el => saveFile(el.dataset.f, EXPORTS[el.dataset.k]());
ACT.expBulk = async () => { const names = { attempts: 'brainova-attempts.csv', responses: 'brainova-responses.csv', topics: 'brainova-topic-performance.csv', students: 'brainova-students.csv', notes: 'brainova-teacher-notes.csv', bank: 'brainova-question-bank.csv', pathways: 'brainova-pathways.csv', pathwayplan: 'brainova-pathway-plan.csv' }; for (const k of Object.keys(names)) await saveFile(names[k], EXPORTS[k]()); };
ACT.expLms = () => saveFile('brainova-lms-import.json', JSON.stringify(lmsPayload(), null, 2));
ACT.expAssess = () => saveFile('brainova-assessments.json', JSON.stringify(S.assessments, null, 2));
ACT.dlKit = () => saveFile('brainova-laravel-integration-kit.md', LARAVEL_KIT);
ACT.gcfgSave = () => {
  const url = document.getElementById('gUrl').value.trim(), token = document.getElementById('gTok').value.trim();
  if (!window.BrainovaAPI) { toast('The Google Sheets adapter did not load.'); return; }
  window.BrainovaAPI.configure({ url, token });
  render(); toast(url ? 'Saved. It will sync quietly in the background from now on.' : 'Cleared. This app will stay local-only.');
};
ACT.gcfgTest = async () => {
  if (!window.BrainovaAPI || !window.BrainovaAPI.isConfigured()) { toast('Enter the URL and token, then Save, first.'); return; }
  toast('Testing…');
  const state = await window.BrainovaAPI.fetchSnapshot();
  toast(state ? 'Connected - the backend responded.' : 'Could not reach it. Check the URL, the token (must match Script Properties exactly) and that the deployment access is set to "Anyone".');
};
ACT.lmsSave = () => { save(); toast('Settings saved in this browser.'); };
ACT.lmsTest = async () => {
  const l = S.settings.lms; if (!l.baseUrl) { toast('Enter the LMS base URL first.'); return; }
  l.status = 'Testing…'; render();
  try { const c = new AbortController(), t = setTimeout(() => c.abort(), 6000); const res = await fetch(l.baseUrl.replace(/\/$/, '') + '/api/assessments/v1/health', { headers: { Authorization: 'Bearer ' + l.token, 'X-Tenant': l.tenant }, signal: c.signal }); clearTimeout(t); l.status = res.ok ? 'Connected' : 'Not connected: server answered ' + res.status; }
  catch (e) { l.status = 'Not connected: the request was blocked or the server could not be reached. This preview cannot call other sites.'; }
  save(); render();
};
ACT.lvSave = () => { const d = U.lvDraft || JSON.parse(JSON.stringify(levels())); U.lvErr = '';
  if (d.some(l => !String(l.name).trim())) U.lvErr = 'Every level needs a name.'; else for (let i = 2; i < d.length; i++) if (!(d[i].min > d[i - 1].min)) U.lvErr = 'Each level must start higher than the one before it.';
  if (d[1].min <= -3) U.lvErr = U.lvErr || 'The second level must start above −3.';
  if (U.lvErr) { render(); return; } d[0].min = -99; S.settings.levels = d; U.lvDraft = null; anCache = new WeakMap(); save(); render(); toast('Levels updated. Reports use the new bands.'); };
ACT.lvReset = () => { S.settings.levels = DEFAULT_LEVELS.map(l => ({ ...l })); U.lvDraft = null; U.lvErr = ''; anCache = new WeakMap(); save(); render(); };
ACT.backup = () => saveFile('brainova-backup.json', JSON.stringify(S));
ACT.restore = el => { const f = el.files[0]; if (!f) return; const r = new FileReader(); r.onload = () => { try { const d = JSON.parse(r.result); if (d.v !== 1 || !Array.isArray(d.bank) || !Array.isArray(d.attempts)) throw 0; S = d; anCache = new WeakMap(); save(); render(); toast('Backup restored.'); } catch (e) { toast('That file is not a Brainova backup.'); } el.value = ''; }; r.readAsText(f); };
ACT.resetSample = () => { if (U.cfm !== 'reset') { U.cfm = 'reset'; render(); return; } U.cfm = null; S = seed(); anCache = new WeakMap(); save(); U.studentId = S.students.find(s => s.grade === 5).id; U.parentId = S.parents[0].id; render(); toast('Sample data restored.'); };
ACT.clearData = () => { if (U.cfm !== 'clear') { U.cfm = 'clear'; render(); return; } U.cfm = null; S.students = []; S.parents = []; S.assessments = []; S.attempts = []; S.supervision = {}; S.sample = false; anCache = new WeakMap(); save(); render(); toast('Sample students and results removed.'); };

// Pulls the LMS's current roster in (same-origin, uses the browser's
// existing LMS login - see AssessmentSystemController::roster()), and
// upserts into S.students/S.parents by id so this can be clicked again
// any time a class list changes, without ever creating a duplicate.
ACT.importRoster = async () => {
  toast('Importing from your LMS…'); render();
  let data;
  try {
    const res = await fetch('/assessment-system/roster', { credentials: 'same-origin' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    data = await res.json();
  } catch (e) {
    toast('Could not reach the LMS roster. Make sure you opened this through the LMS, not as a local file.');
    return;
  }
  // Laravel sends real numeric ids; this app compares ids as strings
  // everywhere else (DOM data-id attributes are always strings, and so
  // is every demo id) - converting once, on the way in, is what makes
  // every existing lookup and every assign-to-student checkbox actually
  // match a real student instead of silently finding nothing.
  let added = 0, updated = 0;
  (data.students || []).forEach(r => {
    const id = String(r.id), parentId = r.parentId != null ? String(r.parentId) : null;
    const ex = S.students.find(s => s.id === id);
    if (ex) { ex.name = r.name; if (r.grade != null) ex.grade = r.grade; if (parentId) ex.parentId = parentId; updated++; }
    else { S.students.push({ id, name: r.name, grade: r.grade, parentId }); added++; }
  });
  (data.parents || []).forEach(r => {
    const id = String(r.id), childIds = (r.childIds || []).map(String);
    const ex = S.parents.find(p => p.id === id);
    if (ex) { ex.name = r.name; ex.childIds = childIds.length ? childIds : ex.childIds; }
    else S.parents.push({ id, name: r.name, childIds });
  });
  save(); render();
  toast(`Imported from your LMS: ${added} new, ${updated} updated.`);
};

// The built-in sample roster (Ayesha Khan and the rest) always uses the
// id pattern seedBase() gives it - BRV-26-101, 102, ... for students,
// par_xxxxx for parents - which a real LMS id (always a plain number,
// stringified) never matches, so this can tell them apart safely even
// after both kinds of student are sitting in the same list.
ACT.removeDemoStudents = () => {
  if (U.cfm !== 'demo') { U.cfm = 'demo'; render(); return; }
  U.cfm = null;
  const removedIds = new Set(S.students.filter(s => String(s.id).startsWith('BRV-26-')).map(s => s.id));
  S.students = S.students.filter(s => !String(s.id).startsWith('BRV-26-'));
  S.parents = S.parents.filter(p => !String(p.id).startsWith('par_'));
  S.attempts = S.attempts.filter(a => !removedIds.has(a.studentId));
  anCache = new WeakMap();
  save(); render();
  toast('Removed the built-in demo students. Your imported LMS roster stays.');
};

/* live */
ACT.liveSel = el => { U.liveSetup = { asmtId: el.value, ids: null }; render(); };
const liveIds = () => { const ls = U.liveSetup || {}, a = asmtById(ls.asmtId) || S.assessments.find(x => x.mode === 'live'); return ls.ids || a.assignedTo.slice(); };
ACT.liveAll = () => { U.liveSetup.ids = null; render(); };
ACT.liveNone = () => { U.liveSetup = Object.assign(U.liveSetup || {}, { ids: [] }); render(); };
ACT.liveTgl = el => { const ls = U.liveSetup = U.liveSetup || { asmtId: null, ids: null }; const ids = liveIds(), i = ids.indexOf(el.dataset.id); if (i >= 0) ids.splice(i, 1); else ids.push(el.dataset.id); ls.ids = ids; render(); };
ACT.liveStart = () => { const ls = U.liveSetup || {}, a = asmtById(ls.asmtId) || S.assessments.find(x => x.mode === 'live' && x.status === 'published'); liveStartSession(a, liveIds()); };
ACT.liveNext = () => { const L = U.live; if (L.phase === 'lobby') liveBegin(0); else liveNextQ(); };
ACT.livePause = () => { const L = U.live; if (L.phase === 'q') { L.phase = 'paused'; L.pausedAt = Date.now(); } else if (L.phase === 'paused') { L.pausedTotal += Date.now() - L.pausedAt; L.phase = 'q'; } render(); };
ACT.liveReveal = () => { liveReveal(); if (U.live && U.live.auto) U.live.autoAt = Date.now() + 6000; };
ACT.liveEndNow = () => liveEnd();
ACT.liveExit = () => { U.live = null; render(); };
ACT.liveAuto = el => { U.live.auto = el.checked; };
ACT.liveSim = el => { U.live.sim = el.checked; };
ACT.liveDev = el => { U.live.device = el.value; render(); };
ACT.liveAns = el => { const L = U.live; if (L.phase !== 'q') return; L.answers[L.idx][L.device] = { raw: el.dataset.v, ms: Math.round(liveElapsed()) }; render(); };
ACT.liveTxtSend = () => { const L = U.live, v = document.getElementById('liveTxt').value.trim(); if (!v || L.phase !== 'q') return; L.answers[L.idx][L.device] = { raw: v, ms: Math.round(liveElapsed()) }; render(); };

/* learning pathways */
ACT.pwGen = el => {
  const att = attById(el.dataset.id); if (!att || !pwReady(att)) return;
  const prev = att.pathways;
  att.pathways = { options: pwBuildOptions(att), selected: prev ? prev.selected : null, notes: prev ? prev.notes : '', status: 'draft', updatedAt: Date.now() };
  save(); render(); toast('Pathway options generated.');
};
ACT.pwSelect = el => { const att = attById(el.dataset.id); if (!att || !att.pathways) return; att.pathways.selected = el.dataset.k; att.pathways.updatedAt = Date.now(); save(); render(); };
ACT.pwNote = el => { const att = attById(el.dataset.id); if (!att || !att.pathways) return; att.pathways.notes = el.value; save(); };
ACT.pwFinalize = el => { const att = attById(el.dataset.id); if (!att || !att.pathways || !att.pathways.selected) { toast('Choose a pathway option first.'); return; } att.pathways.status = 'final'; att.pathways.updatedAt = Date.now(); save(); render(); toast('Pathway finalised.'); };
ACT.pwSend = el => { const att = attById(el.dataset.id); if (!att || !att.pathways) return; att.pathways.status = 'sent'; att.pathways.updatedAt = Date.now(); save(); render(); toast('Sent to the administrator for review.'); };
ACT.pwShare = el => { const att = attById(el.dataset.id); if (!att || !att.pathways || U.role !== 'admin') return; att.pathways.status = 'shared'; att.pathways.updatedAt = Date.now(); save(); render(); toast('Shared with the parent.'); };
ACT.pwReopen = el => { const att = attById(el.dataset.id); if (!att || !att.pathways || U.role !== 'admin') return; att.pathways.status = 'draft'; att.pathways.updatedAt = Date.now(); save(); render(); toast('Reopened for edits.'); };
ACT.pwPdf = el => { const att = attById(el.dataset.id); if (att) downloadPathwayPdf(att); };

/* voice-over and rewards */
ACT.voiceToggle = () => { S.settings.voice.enabled = !S.settings.voice.enabled; save(); render(); };
ACT.voiceTest = () => voiceSample();
ACT.rwUpload = el => {
  const f = el.files[0]; if (!f) return;
  if (f.size > 400 * 1024) { toast('Image is larger than 400 KB. Use a smaller file.'); el.value = ''; return; }
  const k = el.dataset.k, r = new FileReader();
  r.onload = () => { S.settings.rewards.images = S.settings.rewards.images || {}; S.settings.rewards.images[k] = r.result; save(); render(); };
  r.readAsDataURL(f);
};
ACT.rwClear = el => { const k = el.dataset.k; if (S.settings.rewards.images) delete S.settings.rewards.images[k]; save(); render(); };

/* init */
function setDefaultIds() { const g5 = S.students.find(s => s.grade === 5) || S.students[0]; U.studentId = g5 && g5.id; U.parentId = S.parents[0] && S.parents[0].id; }
// The admin-managed collections (students/parents/assessments/bank) sync
// to the Sheet on a short debounce after every save() - if this page gets
// reloaded (e.g. the user re-opens Assessment System from the LMS menu)
// in that short window, the fetch below would otherwise return the
// Sheet's still-older copy and silently wipe out whatever was added
// locally seconds earlier (a freshly imported student, a new question,
// a brand-new assessment) before it finished saving. Merging by id - the
// Sheet's copy of anything it already has, plus anything only the local
// copy knows about - means a record can never just disappear because of
// that timing, no matter how slow or fast the sync turns out to be.
function mergeById(localArr, remoteArr) {
  if (!Array.isArray(remoteArr)) return Array.isArray(localArr) ? localArr : [];
  if (!Array.isArray(localArr) || !localArr.length) return remoteArr;
  const seen = new Set(remoteArr.map(r => r.id));
  const localOnly = localArr.filter(r => !seen.has(r.id));
  return localOnly.length ? remoteArr.concat(localOnly) : remoteArr;
}
function init() {
  S = load(); if (!S) S = seed();
  // Identity must be established BEFORE anything calls save(): save()'s
  // own role check (see core.js) only skips scheduling a full snapshot
  // sync for a signed-in student/parent, and U.lms is still null until
  // this runs - calling ensureSample() (which ends with its own save())
  // first used to fire off exactly the kind of write a student is never
  // allowed to make, on every single page load, which a signed-in
  // student/parent has no Settings page to ever notice or clear.
  const signedIn = applyLmsIdentity(); // true when a real LMS login token took over role/identity
  ensureSample();
  if (!signedIn) setDefaultIds();
  render(); // show the cached/local state immediately, never block on the network
  if (window.BrainovaAPI) {
    window.BrainovaAPI.fetchSnapshot().then(remote => {
      if (!remote) return; // not configured yet, offline, or nothing newer - local stays authoritative
      // The Sheet's own "settings" tab starts out empty until someone
      // saves something there - an empty/missing settings on a fetch
      // just means "nothing saved there yet", never "erase the local
      // defaults", so this keeps whatever's already here instead of
      // replacing it with nothing (ensureSample() below repairs it
      // either way, but keeping real local values is still better than
      // falling back to bare defaults every time).
      if (!remote.settings) remote.settings = S.settings;
      ['students', 'parents', 'assessments', 'bank', 'simpleSets'].forEach(k => { remote[k] = mergeById(S[k], remote[k]); });
      // The backend attaches each attempt's learning pathway (if any and
      // if this role is allowed to see it) directly onto that attempt
      // before sending it here, so att.pathways needs no extra handling.
      S = remote; anCache = new WeakMap(); ensureSample(); if (!signedIn) setDefaultIds(); persistLocal(); resolveVsPending_(); render();
      // Reset the "what have I already sent" baseline to match what the
      // Sheet just confirmed it has, so the next edit only resyncs what
      // actually changes from here - not everything, just to catch up.
      if (window.BrainovaAPI.markSynced) window.BrainovaAPI.markSynced(S);
      // A lead's assessment is assigned via the admin token moments
      // before their invite email is sent (see AssessmentBookingController
      // ::createLeadAndSend()), but a Google Sheets write can take a few
      // seconds to become visible to the very next read - if this first
      // bootstrap landed just before that, quietly retry instead of
      // leaving "nothing assigned" on screen until a manual reload.
      if (U.lms && U.lms.role === 'student' && String(U.lms.uid).startsWith('LEAD-') && !assignedFor(U.lms.uid).length) {
        retryLeadAssignment_(0);
      }
    }).catch(() => { /* stay on the local/cached copy */ });
  }
}
/**
 * The "View report" button's deep link (identity.js's 'vs' param) can
 * only land on Students+this student selected at first, because the
 * real data (a LEAD-* id the local cache has never seen) isn't in S yet
 * at that point - this is what finishes the jump to that student's own
 * latest report the moment real data arrives, so admin/teacher doesn't
 * have to click through Students by hand. Only runs once (U.vsPending
 * is cleared immediately), and only actually jumps to a report when
 * there's a finished attempt to show - otherwise Students+selected is
 * already the right, honest fallback ("no attempts yet").
 */
function resolveVsPending_() {
  if (!U.vsPending) return;
  const sid = U.vsPending;
  U.vsPending = null;
  const attempts = S.attempts.filter(a => a.studentId === sid && !a.preview).sort((a, b) => b.finished - a.finished);
  if (attempts.length) { U.view = 'report'; U.params = { id: attempts[0].id }; }
}
function retryLeadAssignment_(n) {
  if (n >= 4 || !window.BrainovaAPI) return;
  setTimeout(() => {
    window.BrainovaAPI.fetchSnapshot().then(remote => {
      if (!remote) return retryLeadAssignment_(n + 1);
      if (!remote.settings) remote.settings = S.settings;
      ['students', 'parents', 'assessments', 'bank', 'simpleSets'].forEach(k => { remote[k] = mergeById(S[k], remote[k]); });
      S = remote; anCache = new WeakMap(); ensureSample(); persistLocal();
      if (window.BrainovaAPI.markSynced) window.BrainovaAPI.markSynced(S);
      if (assignedFor(U.lms.uid).length) render(); else retryLeadAssignment_(n + 1);
    }).catch(() => retryLeadAssignment_(n + 1));
  }, 4000);
}
init();
