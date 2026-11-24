/* ===================== SHELL, ROUTER, DASHBOARD, ASSESSMENTS, BUILDER ===================== */
const NAV = {
  admin: [['dash', 'Dashboard', 'dash'], ['assess', 'Assessments', 'list'], ['bank', 'Question bank', 'bank'], ['students', 'Students', 'stu'], ['results', 'Results', 'res'], ['pathways', 'Pathways', 'list'], ['export', 'Exports', 'exp'], ['integration', 'LMS integration', 'link'], ['settings', 'Settings', 'cog']],
  teacher: [['dash', 'Dashboard', 'dash'], ['assess', 'Assessments', 'list'], ['live', 'Live class', 'live'], ['bank', 'Question bank', 'bank'], ['results', 'Results', 'res'], ['pathways', 'Pathways', 'list'], ['students', 'Students', 'stu'], ['export', 'Exports', 'exp']],
  student: [['my', 'My assessments', 'home'], ['hist', 'History', 'hist']],
  parent: [['parent', 'My child', 'home']]
};
const HOME = { admin: 'dash', teacher: 'dash', student: 'my', parent: 'parent' };
const ROLE_NAME = { admin: 'Administrator', teacher: 'Teacher', student: 'Student', parent: 'Parent' };

function go(view, params) { U.view = view; U.params = params || {}; render(); scrollTop(); }
function setRole(r) {
  U.role = r; U.view = HOME[r]; U.params = {};
  if (!U.studentId) U.studentId = S.students.find(s => s.grade === 5).id;
  if (!U.parentId) U.parentId = S.parents[0].id;
  render(); scrollTop();
}
function render() {
  const root = document.getElementById('root'); if (!root) return;
  if (U.run) { root.innerHTML = runView(); afterRender(); return; }
  if (U.live) { root.innerHTML = liveView(); afterRender(); return; }
  const nav = NAV[U.role];
  const viewFn = VIEWS[U.view] || VIEWS[HOME[U.role]];
  let body = ''; try { body = viewFn(); } catch (e) { console.error(e); body = `<div class="banner bad">Something went wrong while drawing this page: ${esc(e.message)}</div>`; }
  // Once the LMS has signed someone in (see identity.js), the role and
  // the student/parent being viewed are fixed by their login token, not
  // a free picker - otherwise a logged-in student could just select
  // someone else's name here and see their data.
  const asRole = U.lms ? '' : U.role === 'student' ? field('Viewing as', `<select data-m="studentId" data-r aria-label="Student">${optList(S.students.map(s => ({ v: s.id, l: `${s.name} (Grade ${s.grade})` })), U.studentId)}</select>`) :
    U.role === 'parent' ? field('Viewing as', `<select data-m="parentId" data-r aria-label="Parent">${optList(S.parents.map(p => ({ v: p.id, l: p.name })), U.parentId)}</select>`) : '';
  const roleArea = U.lms
    ? `<div class="pill ok" title="Opened from the Brainova LMS with a signed login link.">${esc(U.lms.name || ROLE_NAME[U.lms.role])} &middot; ${ROLE_NAME[U.lms.role]}</div>`
    : `<div class="roles" role="group" aria-label="Demo role">${['admin', 'teacher', 'student', 'parent'].map(r => `<button data-a="role" data-v="${r}" aria-pressed="${U.role === r}">${ROLE_NAME[r]}</button>`).join('')}</div>`;
  root.innerHTML = `<div class="app"><aside class="side"><div class="brand">${LOGO}<div><b>BRAINOVA</b><span>Assessment &amp; Placement</span></div></div>
    <nav class="nav" aria-label="Main">${nav.map(n => `<button data-a="go" data-v="${n[0]}" ${U.view === n[0] || (U.view === 'report' && n[0] === 'results') || (U.view === 'builder' && n[0] === 'assess') ? 'aria-current="page"' : ''}>${ico(n[2])}${n[1]}</button>`).join('')}</nav>
    <div class="note">${S.sample ? 'Sample data is loaded so you can explore every screen. Reset or clear it in Settings (Administrator).' : 'Your data is stored in this browser only.'}</div></aside>
    <div class="main"><div class="topbar"><div class="row">${roleArea}${asRole ? `<div style="min-width:220px">${asRole}</div>` : ''}</div>
    <span class="pill ${U.lms ? 'ok' : 'pur'}" title="${U.lms ? 'Opened from the Brainova LMS with a signed login link. The role above is not a choice.' : 'Roles here are a demonstration. Production uses Brainova LMS sign-in and role-based permissions.'}">${U.lms ? 'Signed in via LMS' : 'Demo roles, no sign-in'}</span></div>
    <div class="page">${body}</div></div></div><div id="toast" class="toast" role="status" ${U.toast ? '' : 'hidden'}>${esc(U.toast)}</div>`;
  afterRender();
}
function afterRender() { if (U.run) runTick(true); if (U.live) liveTick(true); }
const VIEWS = {};

/* ---------- Dashboard ---------- */
function filteredAttempts() {
  const f = U.filters, now = Date.now();
  return S.attempts.filter(a => {
    if (a.preview) return false;
    if (f.range !== 'all' && now - a.finished > +f.range * 86400000) return false;
    if (f.subject && !a.subjectList.includes(f.subject)) return false;
    if (f.grade && String(a.grade) !== f.grade) return false;
    if (f.course && a.courseId !== f.course) return false;
    if (f.asmt && a.asmtId !== f.asmt) return false;
    if (f.student && a.studentId !== f.student) return false;
    if (f.topic && !a.responses.some(r => r.topic === f.topic)) return false;
    if (f.level !== '' && String(AN(a).overallIdx) !== f.level) return false;
    return true;
  });
}
VIEWS.dash = function () {
  const f = U.filters, at = filteredAttempts(), tpl = [...new Set(S.bank.map(q => q.topic))];
  const latest = {}; at.forEach(a => { const k = a.studentId; if (!latest[k] || latest[k].finished < a.finished) latest[k] = a; });
  const lat = Object.values(latest).map(a => ({ a, an: AN(a) }));
  const support = lat.filter(x => x.an.overallIdx != null && (x.an.overallIdx <= 1 || x.an.topics.some(t => !t.probe && t.idx === 0)));
  const adv = lat.filter(x => x.an.overallIdx != null && x.an.overallIdx >= 3);
  const subj = {}; at.forEach(a => a.responses.forEach(r => { if (r.status !== 'correct' && r.status !== 'incorrect') return; const s = subj[r.subject] = subj[r.subject] || { n: 0, c: 0 }; s.n++; if (r.status === 'correct') s.c++; }));
  const gr = {}; at.forEach(a => { const g = gr[a.grade] = gr[a.grade] || { acc: [], lv: [] }; const an = AN(a); if (an.accuracy != null) g.acc.push(an.accuracy); if (an.overallIdx != null) g.lv.push(an.overallIdx); });
  const tp = {}; at.forEach(a => AN(a).topics.forEach(t => { if (t.probe || t.idx == null) return; const o = tp[t.topic] = tp[t.topic] || { c: [0, 0, 0, 0, 0], n: 0 }; o.c[t.idx]++; o.n++; }));
  const tpRows = Object.entries(tp).map(([k, v]) => ({ topic: k, ...v, low: (v.c[0] + v.c[1]) / v.n })).sort((a, b) => b.low - a.low);
  const pub = S.assessments.filter(a => a.status === 'published');
  return `<div class="page-head"><div><h1>Dashboard</h1><p>Placement evidence across assessments. Levels are estimates from the questions administered and are meant to support teacher judgement.</p></div>
    <div class="row">${U.role === 'teacher' || U.role === 'admin' ? '<button class="btn" data-a="newAsmt">New assessment</button>' : ''}</div></div>
    ${pwInbox()}
    ${asmtById('asm_g5check') && U.role !== 'student' ? '<div class="banner info"><div><b>Sample test ready: Grade 5 Mathematics Check.</b> Take it as a student, then come back to Results to see the report as Administrator, Teacher or Parent.</div><button class="btn sm teal" data-a="trySample">Take it as a Grade 5 student</button></div>' : ''}
    <div class="grid g4"><div class="card kpi"><b>${S.assessments.length}</b><span>Assessments created</span></div><div class="card kpi"><b>${pub.length}</b><span>Active (published)</span></div>
    <div class="card kpi"><b>${S.attempts.filter(a => !a.preview).length}</b><span>Completed attempts</span></div><div class="card kpi"><b>${S.students.length}</b><span>Registered students</span></div></div>
    <div class="card"><div class="filters">
      ${field('Date', `<select data-m="filters.range" data-r>${optList([{ v: 'all', l: 'All time' }, { v: '7', l: 'Last 7 days' }, { v: '30', l: 'Last 30 days' }, { v: '90', l: 'Last 90 days' }], f.range)}</select>`)}
      ${field('Subject', `<select data-m="filters.subject" data-r>${optList(SYLLABUS.map(s => s.subject), f.subject, 'All subjects')}</select>`)}
      ${field('Grade', `<select data-m="filters.grade" data-r>${optList([1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(g => ({ v: g, l: 'Grade ' + g })), f.grade, 'All grades')}</select>`)}
      ${field('Course', `<select data-m="filters.course" data-r>${optList(COURSES.map(c => ({ v: c.id, l: c.name })), f.course, 'All courses')}</select>`)}
      ${field('Assessment', `<select data-m="filters.asmt" data-r>${optList(S.assessments.map(a => ({ v: a.id, l: a.title })), f.asmt, 'All assessments')}</select>`)}
      ${field('Topic', `<select data-m="filters.topic" data-r>${optList(tpl, f.topic, 'All topics')}</select>`)}
      ${field('Proficiency', `<select data-m="filters.level" data-r>${optList(levels().map((l, i) => ({ v: i, l: l.name })), f.level, 'Any level')}</select>`)}
      ${field('Student', `<select data-m="filters.student" data-r>${optList(S.students.map(s => ({ v: s.id, l: s.name })), f.student, 'All students')}</select>`)}
      <button class="btn ghost" data-a="clearFilters">Clear filters</button></div></div>
    ${at.length ? `<div class="grid g2">
      <div class="card"><h2>Subject-wise accuracy</h2>${bars(Object.entries(subj).map(([k, v]) => ({ label: k, value: v.c / v.n, text: pct(v.c / v.n) + ' · ' + v.n + ' answers' })))}</div>
      <div class="card"><h2>Grade-wise accuracy</h2>${bars(Object.entries(gr).sort((a, b) => a[0] - b[0]).map(([k, v]) => ({ label: 'Grade ' + k, value: avg(v.acc), text: pct(avg(v.acc)) + ' · avg ' + (v.lv.length ? levels()[Math.round(avg(v.lv))].name : '—') })), { teal: true })}</div></div>
      <div class="card"><div class="row sp"><h2>Topic-wise learning gaps</h2>${levelLegend()}</div><p class="muted small" style="margin-bottom:10px">Share of students at each estimated level per topic, most gaps first. Topics with limited evidence are not counted.</p>
        <div class="tw"><table><thead><tr><th>Topic</th><th>Students</th><th style="min-width:240px">Level distribution</th><th class="num">At Foundation or Developing</th></tr></thead><tbody>${tpRows.map(r => `<tr><td>${esc(r.topic)}</td><td class="num">${r.n}</td><td>${levelStack(r.c)}</td><td class="num">${pct(r.low)}</td></tr>`).join('')}</tbody></table></div></div>
      <div class="grid g2">
        <div class="card"><h2>Students who may need support <span class="pill warn">${support.length}</span></h2>${support.length ? `<ul class="ul plain">${support.map(x => `<li><a href="#" data-a="openAtt" data-id="${x.a.id}"><b>${esc(x.a.studentName)}</b></a> <span class="muted small">Grade ${x.a.grade} · ${esc(x.a.title)}</span><br><span class="small">${x.an.topics.filter(t => !t.probe && t.idx != null && t.idx <= 1).map(t => esc(t.topic) + ' (' + esc(lvName(t.idx)) + ')').join(', ') || 'Overall ' + esc(lvName(x.an.overallIdx))}</span></li>`).join('')}</ul>` : '<p class="muted">No students match the current filters.</p>'}</div>
        <div class="card"><h2>Ready for advanced learning <span class="pill ok">${adv.length}</span></h2>${adv.length ? `<ul class="ul plain">${adv.map(x => `<li><a href="#" data-a="openAtt" data-id="${x.a.id}"><b>${esc(x.a.studentName)}</b></a> <span class="muted small">Grade ${x.a.grade} · ${esc(x.a.title)}</span><br><span class="small">${lvPill(x.an.overallIdx)} ${esc(finalLevelText(x.a, x.an))}</span></li>`).join('')}</ul>` : '<p class="muted">No students match the current filters.</p>'}</div></div>
      <div class="card"><h2>Recent attempts</h2>${attemptTable(at.slice().sort((a, b) => b.finished - a.finished).slice(0, 8))}</div>`
      : '<div class="empty">No attempts match these filters. Clear the filters or widen the date range.</div>'}`;
};
function attemptTable(list, opts = {}) {
  if (!list.length) return '<div class="empty">No attempts yet.</div>';
  return `<div class="tw"><table><thead><tr><th>Student</th><th>Assessment</th><th>Date</th><th class="num">Score</th><th>Level</th><th>Recommendation</th><th>Status</th></tr></thead><tbody>${list.map(a => { const an = AN(a); return `<tr class="click" data-a="openAtt" data-id="${a.id}"><td><b>${esc(a.studentName)}</b><br><span class="muted small">Grade ${a.grade}</span></td><td>${esc(a.title)}<br><span class="muted small">${a.mode === 'live' ? 'Live' : a.model === 'adaptive' ? 'Adaptive' : 'Fixed'}</span></td><td class="nowrap">${fmtDate(a.finished)}</td>
    <td class="num">${an.marks}/${an.maxMarks}<br><span class="muted small">${pct(an.accuracy)} accuracy</span></td><td>${lvPill(an.overallIdx)}</td><td style="max-width:260px">${esc(finalLevelText(a, an))}</td>
    <td>${an.pending ? '<span class="pill warn">Needs scoring</span> ' : ''}${a.released ? '<span class="pill ok">Released</span>' : '<span class="pill">Teacher only</span>'}</td></tr>`; }).join('')}</tbody></table></div>`;
}

/* ---------- Assessments list ---------- */
const AGE_GROUPS = ['2–3 years', '3–4 years', '4–5 years', '5–6 years', '6–7 years', '7–8 years', '8–9 years', '9–10 years', '10–11 years', '11–12 years', '12–13 years', '13–14 years', '14–15 years', '15–16 years', '16+ years', '5–7 years', '7–9 years', '9–11 years', '8–12 years', '11–14 years', '12–16 years', '14+ years'];
function asgPanel(a) {
  const cand = S.students.filter(s => !a.grades.length || a.grades.includes(s.grade));
  return `<div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line)"><div class="row sp"><h3>Assign to students</h3><div class="row"><button class="btn sm ghost" data-a="asgAll" data-id="${a.id}">Assign all listed</button><button class="btn sm ghost" data-a="asgNone" data-id="${a.id}">Clear</button></div></div>
  <div class="grid g3" style="margin:10px 0">${cand.map(s => { const n = attemptsOf(a.id, s.id).length; return `<label class="chk"><input type="checkbox" data-a="asgTgl" data-id="${a.id}" data-sid="${s.id}" ${a.assignedTo.includes(s.id) ? 'checked' : ''}><span>${esc(s.name)} <span class="muted small">Grade ${s.grade == null ? '—' : s.grade}${n ? ' · ' + n + ' attempt' + (n === 1 ? '' : 's') : ''}</span></span></label>`; }).join('')}</div>
  <div class="row"><label class="small">Attempts allowed per student <select data-a="asgAttempts" data-id="${a.id}">${[1, 2, 3, 4, 5].map(n => `<option value="${n}" ${+a.attempts === n ? 'selected' : ''}>${n}</option>`).join('')}</select></label><span class="muted small">Raise this to let students try again. Every attempt keeps its own report.</span></div></div>`;
}
VIEWS.assess = function () {
  const list = S.assessments.slice().sort((a, b) => b.created - a.created);
  return `<div class="page-head"><div><h1>Assessments</h1><p>Create, publish and assign assessments for Brainova School and short courses.</p></div><button class="btn" data-a="newAsmt">New assessment</button></div>
  <div class="stack">${list.map(a => {
    const n = S.attempts.filter(x => x.asmtId === a.id && !x.preview).length, c = courseOf(a), tp = a.topics.length, cf = leadIntakeConflicts(a);
    return `<div class="card"><div class="row sp"><div style="min-width:0"><div class="row" style="margin-bottom:6px"><span class="pill ${a.status === 'published' ? 'ok' : a.status === 'closed' ? '' : 'warn'}">${a.status === 'published' ? 'Published' : a.status === 'closed' ? 'Closed' : 'Draft'}</span><span class="pill pur">${a.application === 'course' ? 'Short course' : 'School'}</span><span class="pill">${a.model === 'adaptive' ? 'Adaptive' : 'Fixed difficulty'}</span><span class="pill">${a.mode === 'live' ? 'Teacher-led live' : 'Independent timed'}</span>${a.leadIntake ? '<span class="pill ok">Public booking page</span>' : ''}${cf.length ? `<span class="pill warn" title="Also covered by: ${esc(cf.map(o => o.title || 'Untitled assessment').join(', '))}">Clashes with ${cf.length} other${cf.length === 1 ? '' : 's'} on public page</span>` : ''}</div>
      <h3>${esc(a.title || 'Untitled assessment')}</h3><p class="muted small">${esc(a.purpose)} · ${c ? esc(c.name) + ' · ' : ''}${esc(subjectsOf(a).join(', ') || 'No subject')} · ${a.grades.length ? 'Grade ' + a.grades.join(', ') : 'All grades'} · ${(a.syllabus || []).length ? esc(a.syllabus.join('/')) + ' · ' : ''}${tp} topic${tp === 1 ? '' : 's'} · ${a.model === 'fixed' ? sumBy(DIFF_KEYS, k => a.dist[k] || 0) + ' questions' : 'up to ' + a.adaptive.max + ' questions'} · ${a.assignedTo.length} assigned · ${n} attempt${n === 1 ? '' : 's'}</p></div>
      <div class="row">${a.status === 'draft' ? `<button class="btn sm" data-a="editAsmt" data-id="${a.id}">Edit</button>` : ''}${a.status === 'published' && a.mode === 'live' ? `<button class="btn sm teal" data-a="liveSetup" data-id="${a.id}">Start live session</button>` : ''}
      ${a.status === 'published' ? `<button class="btn sm ghost" data-a="closeAsmt" data-id="${a.id}">Close</button>` : ''}${a.status === 'closed' ? `<button class="btn sm ghost" data-a="reopenAsmt" data-id="${a.id}">Reopen</button>` : ''}
      <button class="btn sm ghost" data-a="previewAsmt" data-id="${a.id}">Preview</button>${a.status !== 'draft' ? `<button class="btn sm ${U.asgOpen === a.id ? '' : 'ghost'}" data-a="asgOpen" data-id="${a.id}">Assign and attempts</button>` : ''}<button class="btn sm ghost" data-a="dupAsmt" data-id="${a.id}">Duplicate</button><button class="btn sm ghost" data-a="asmtResults" data-id="${a.id}">Results</button></div></div>${U.asgOpen === a.id ? asgPanel(a) : ''}</div>`; }).join('')}</div>`;
};

/* ---------- Builder ---------- */
const PURPOSES = ['Diagnostic', 'Admission', 'Placement', 'Academic', 'Topic proficiency'];
const STEPS = ['Basics', 'Subjects and topics', 'Model and difficulty', 'Delivery and rules', 'Preview and publish'];
function objectivesFor(subject, topic) { return [...new Set(S.bank.filter(q => q.subject === subject && q.topic === topic).map(q => q.objective))]; }
function selectedTopic(d, s, t) { return d.topics.some(x => x.subject === s && x.topic === t); }
// Live, read-only view of every bank question the draft's current
// topics/grades would actually draw from - shown on every builder
// step (not just the final preview) so changing a grade chip, a
// topic checkbox or anything else immediately shows its effect here,
// the same table the Question bank itself uses. Ignores the
// difficulty distribution/adaptive stretch on purpose - this is "what
// could be used," not "what one student would actually get" (that
// exact draw is still Step 5's own preview).
function draftPoolPreview(d) {
  const above = d.model === 'adaptive' ? undefined : 0;
  const up = above === undefined ? stretchCap(d) : above;
  const keys = d.topics.length ? new Set(d.topics.map(t => topicKey(t.subject, t.topic))) : null;
  const ex = new Set(d.excludeObj || []);
  const pool = S.bank.filter(q => q.status === 'approved' && (!keys || keys.has(topicKey(q.subject, q.topic))) && gradeOk(q, d, up) && !ex.has(q.subject + '|' + q.topic + '|' + q.objective));
  const lim = 100;
  const scope = keys ? '' : ' <span class="muted small">(every subject and topic - pick topics in Step 2 to narrow this down)</span>';
  // For a fixed-difficulty test (once topics are picked), this table
  // IS the question list - a checked row is used by every student,
  // nobody gets a different random draw. The first time there's
  // something to pick from, seed it from one random draw (the same
  // logic a real attempt used to make on its own) so there's always a
  // sensible starting point; after that only checking/unchecking rows
  // here changes anything, same as the Question bank's own search is
  // never auto-reset once touched.
  const pinnable = d.model === 'fixed' && !!keys;
  if (pinnable && !(d.pinnedIds || []).length && pool.length) d.pinnedIds = buildFixed(d, S.bank, 'seed-' + d.id).items.map(q => q.id);
  const pinned = new Set(pinnable ? (d.pinnedIds || []) : []);
  const shown = pinnable ? pool.slice().sort((x, y) => (pinned.has(y.id) ? 1 : 0) - (pinned.has(x.id) ? 1 : 0)) : pool;
  const pinnedCount = pool.filter(q => pinned.has(q.id)).length;
  return `<div class="card"><div class="row sp"><h3>Matching questions in the bank${scope}</h3><span class="row">${pinnable ? `<span class="pill ${pinnedCount ? 'ok' : 'warn'}">${pinnedCount} selected</span>` : ''}<span class="pill ${pool.length ? '' : 'warn'}">${pool.length} matching</span></span></div>
    ${pinnable ? '<p class="muted small" style="margin-bottom:8px">Every student gets exactly the checked questions below - uncheck one to drop it, check another to use it instead.</p>' : ''}
    ${pool.length ? `<div class="tw" style="max-height:380px;overflow:auto"><table><thead><tr>${pinnable ? '<th></th>' : ''}<th>ID</th><th>Topic</th><th>Level</th><th>Grades</th><th>Type</th><th>Question</th></tr></thead><tbody>${shown.slice(0, lim).map(q => `<tr>${pinnable ? `<td><input type="checkbox" data-a="tglPinnedQ" data-id="${q.id}" ${pinned.has(q.id) ? 'checked' : ''}></td>` : ''}<td class="mono small nowrap">${esc(q.id)}</td><td>${esc(q.topic)}<br><span class="muted small">${esc(q.subject)}</span></td><td>${DIFF[q.diff].name}</td><td class="nowrap">${q.grades ? `${q.grades[0]}–${q.grades[1]}` : '<span class="pill warn">None</span>'}</td><td>${esc(qTypeName(q.type))}</td><td style="min-width:220px">${esc(q.text.slice(0, 110))}${q.text.length > 110 ? '…' : ''}</td></tr>`).join('')}</tbody></table></div>${pool.length > lim ? `<p class="muted small" style="margin-top:8px">Showing the first ${lim} of ${pool.length}${pinnable ? ', selected ones first' : ''}.</p>` : ''}`
      : '<p class="muted small">No approved questions match these grades and topics yet.</p>'}</div>`;
}
VIEWS.builder = function () {
  const d = U.draft; if (!d) { return '<div class="empty">No assessment is being edited. <button class="btn sm" data-a="newAsmt">Start a new one</button></div>'; }
  const body = [null, bStep1, bStep2, bStep3, bStep4, bStep5][U.step](d);
  return `<div class="page-head"><div><h1>${d.status === 'draft' && S.assessments.some(a => a.id === d.id) ? 'Edit assessment' : 'New assessment'}</h1><p>${esc(d.title || 'Untitled')}</p></div><div class="row"><button class="btn ghost" data-a="saveDraft">Save draft</button><button class="btn ghost" data-a="go" data-v="assess">Close</button></div></div>
    <div class="steps" role="tablist">${STEPS.map((s, i) => `<button data-a="step" data-n="${i + 1}" ${U.step === i + 1 ? 'aria-current="step"' : ''}>${i + 1}. ${s}</button>`).join('')}</div>${body}
    ${draftPoolPreview(d)}
    <div class="row sp"><button class="btn ghost" data-a="step" data-n="${Math.max(1, U.step - 1)}" ${U.step === 1 ? 'disabled' : ''}>Back</button>${U.step < 5 ? `<button class="btn" data-a="step" data-n="${U.step + 1}">Continue</button>` : ''}</div>`;
};
function bStep1(d) {
  const c = courseOf(d);
  return `<div class="card"><div class="grid g2">
    ${field('Assessment title', inp('draft.title', d.title, 'text', 'placeholder="e.g. Grade 5 Mathematics Term Diagnostic"'))}
    ${field('Purpose', `<select data-m="draft.purpose">${optList(PURPOSES, d.purpose)}</select>`)}
    ${field('Application', `<select data-m="draft.application" data-r>${optList([{ v: 'school', l: 'Brainova School (Grades 1–10)' }, { v: 'course', l: 'Brainova Short Course' }], d.application)}</select>`)}
    ${d.application === 'course' ? field('Course', `<select data-m="draft.courseId" data-r>${optList(COURSES.map(x => ({ v: x.id, l: x.name })), d.courseId, 'Choose a course')}</select>`) : field('Assessment type', `<select data-m="draft.level">${optList(['Grade level', 'Complete syllabus', 'Topic specific', 'Placement'], d.level)}</select>`)}
    ${field('Student age group', `<select data-m="draft.ageGroup">${optList(AGE_GROUPS, d.ageGroup)}</select>`)}
    ${field('Academic level', `<select data-m="draft.level">${optList(['Foundation', 'Grade level', 'Intermediate', 'Advanced', 'Challenging', 'Mastery', 'Exceptional', 'Mixed'], d.level)}</select>`)}
    ${field('Passing criteria (% score, 0 for none)', inp('draft.passPct', d.passPct, 'number', 'min="0" max="100"'))}</div>
    <div class="stack" style="margin-top:14px"><div><div class="eyebrow" style="margin-bottom:6px">Grades included</div><div class="chips">${[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map(g => `<button class="chip" data-a="tglGrade" data-g="${g}" aria-pressed="${d.grades.includes(g)}">Grade ${g}</button>`).join('')}</div><p class="muted small" style="margin-top:6px">Questions are drawn only from items whose grade range overlaps these grades. Leave empty to ignore grade.</p></div>
    <div><div class="eyebrow" style="margin-bottom:6px">Syllabus</div><div class="chips">${SYLLABUSES.map(s => `<button class="chip" data-a="tglSyllabus" data-s="${esc(s)}" aria-pressed="${(d.syllabus || []).includes(s)}">${esc(s)}</button>`).join('')}</div><p class="muted small" style="margin-top:6px">Only used to match a visitor on the public booking page who named this syllabus. Leave empty to match any syllabus.</p></div>
    <div class="sub">${chk('draft.leadIntake', !!d.leadIntake, 'Offer this to visitors on the public "book an assessment" page')}<p class="small muted" style="margin-top:6px">Only published assessments with this turned on, and whose grade and subject match, are ever used for a public visitor who isn’t already a student. Turn this on once you’re happy with the questions here - leave it off while you’re still building or checking it.</p>
    ${(() => { const cf = leadIntakeConflicts(d); return cf.length ? `<div class="banner warn" style="margin-top:8px">Grade and subject already covered by ${cf.length === 1 ? 'another published assessment' : cf.length + ' other published assessments'} on the public page: ${cf.map(o => esc(o.title || 'Untitled assessment')).join(', ')}. When two match the same visitor, only one is used and which one is not guaranteed - turn this off there, or here, so only one covers each grade and subject.</div>` : ''; })()}</div>
    <div class="sub"><div class="grid g2">${field('Allow questions above grade level', `<select data-m="draft.stretch" data-t="num" data-r>${optList([{ v: 0, l: 'No. Stay within the selected grades' }, { v: 1, l: 'Up to 1 grade above (recommended)' }, { v: 2, l: 'Up to 2 grades above' }], d.stretch == null ? 0 : d.stretch)}</select>`)}<p class="small muted" style="align-self:end">Older children and strong readers often solve work from the next grade. Use this with the age group above so that questions suit the child’s age and ability, not only the grade.${d.model !== 'adaptive' ? ' Only takes effect for an Adaptive test (Step 3) - a Fixed-difficulty test always stays within the selected grades exactly.' : ''}</p></div></div>
    ${c ? `<div class="banner info">Course levels: ${c.levels.map(esc).join(' → ')}. <button class="btn sm ghost" data-a="useCourseTopics">Use this course’s topics</button></div>` : ''}
    ${field('Target learning outcomes', `<textarea data-m="draft.outcomes" placeholder="What should this assessment tell you?">${esc(d.outcomes)}</textarea>`)}
    ${field('Instructions shown to students', `<textarea data-m="draft.instructions">${esc(d.instructions)}</textarea>`)}</div></div>`;
}
function bStep2(d) {
  const subs = SYLLABUS.map(s => s.subject), active = new Set(d.topics.map(t => t.subject).concat(d._subs || []));
  return `<div class="card"><h3>Subjects</h3><div class="chips" style="margin:8px 0 6px">${subs.map(s => `<button class="chip" data-a="tglSubject" data-s="${esc(s)}" aria-pressed="${active.has(s)}">${esc(s)}</button>`).join('')}</div>
    <p class="muted small">Select one or more subjects, then choose units, topics from different terms, or the complete syllabus. Counts show approved questions per difficulty.</p></div>
    ${SYLLABUS.filter(s => active.has(s.subject)).map(sy => {
      const all = sy.units.flatMap(u => u.topics), nSel = all.filter(t => selectedTopic(d, sy.subject, t)).length;
      return `<div class="card"><div class="row sp"><h2>${esc(sy.subject)} <span class="pill">${nSel}/${all.length} topics</span></h2><div class="row"><button class="btn sm ghost" data-a="selSubject" data-s="${esc(sy.subject)}">Select complete syllabus</button><button class="btn sm ghost" data-a="clrSubject" data-s="${esc(sy.subject)}">Clear</button></div></div>
      ${sy.units.map(u => { const unitAll = u.topics.every(t => selectedTopic(d, sy.subject, t)); return `<div class="sub" style="margin-top:10px"><label class="chk"><input type="checkbox" data-a="tglUnit" data-s="${esc(sy.subject)}" data-u="${esc(u.unit)}" ${unitAll ? 'checked' : ''}><span><b>${esc(u.unit)}</b> <span class="muted small">Term ${u.term}</span></span></label>
        <div class="stack" style="margin:8px 0 0 24px">${u.topics.map(t => { const m = topicMeta(sy.subject, t), tot = m.S + m.H + m.E, ob = objectivesFor(sy.subject, t); return `<div><div class="row sp"><label class="chk"><input type="checkbox" data-a="tglTopic" data-s="${esc(sy.subject)}" data-t="${esc(t)}" ${selectedTopic(d, sy.subject, t) ? 'checked' : ''}><span>${esc(t)}</span></label>
          <span class="small ${tot ? 'muted' : ''}">${tot ? `Strengthen ${m.S} · Hard ${m.H} · Extend ${m.E}` : '<span class="pill warn">No approved questions yet</span>'}</span></div>
          ${ob.length && selectedTopic(d, sy.subject, t) ? `<details style="margin:4px 0 0 24px"><summary class="small muted">Learning objectives (${ob.filter(o => !(d.excludeObj || []).includes(sy.subject + '|' + t + '|' + o)).length}/${ob.length} included)</summary><div class="stack" style="margin-top:6px">${ob.map(o => `<label class="chk small"><input type="checkbox" data-a="tglObj" data-k="${esc(sy.subject + '|' + t + '|' + o)}" ${(d.excludeObj || []).includes(sy.subject + '|' + t + '|' + o) ? '' : 'checked'}><span>${esc(o)}</span></label>`).join('')}</div></details>` : ''}</div>`; }).join('')}</div></div>`; }).join('')}</div>`;
    }).join('')}`;
}
function bStep3(d) {
  const tot = sumBy(DIFF_KEYS, k => +d.dist[k] || 0);
  return `<div class="card"><h3>Question model</h3><div class="grid g2" style="margin-top:10px">
    <label class="sub chk" style="cursor:pointer"><input type="radio" name="model" data-m="draft.model" data-r value="fixed" ${d.model === 'fixed' ? 'checked' : ''}><span><b>Fixed difficulty</b><br><span class="muted small">You decide how many questions of each difficulty (Strengthen up to Exceptional) every student receives.</span></span></label>
    <label class="sub chk" style="cursor:pointer"><input type="radio" name="model" data-m="draft.model" data-r value="adaptive" ${d.model === 'adaptive' ? 'checked' : ''}><span><b>Adaptive</b><br><span class="muted small">The next question depends on the student’s evidence so far, so each student sees a different path.</span></span></label></div></div>
  ${d.model === 'fixed' ? `<div class="card"><h3>Difficulty distribution</h3><div class="grid g4" style="margin:10px 0">
    ${DIFF_KEYS.map(k => field(DIFF[k].name, inp('draft.dist.' + k, d.dist[k] || 0, 'number', 'min="0" max="60" data-r'))).join('')}<div class="kpi" style="justify-content:flex-end"><b>${tot}</b><span>Total questions</span></div></div>
    <div class="row"><span class="muted small">Quick split</span>${[[10, 3, 4, 3, 0, 0, 0], [15, 5, 6, 4, 0, 0, 0], [20, 6, 8, 6, 0, 0, 0], [24, 6, 10, 8, 0, 0, 0], [24, 4, 6, 6, 4, 3, 1], [30, 9, 12, 9, 0, 0, 0]].map(p => `<button class="btn sm ghost" data-a="splitDist" data-s="${p[1]}" data-h="${p[2]}" data-e="${p[3]}" data-c="${p[4]}" data-m2="${p[5]}" data-x="${p[6]}">${p[0]} questions (${p.slice(1).filter((x, i) => i < 3 || x).join('/')})${p[4] ? ' with stretch' : ''}</button>`).join('')}</div>
    <div class="legend" style="margin-top:12px"><span><b>Strengthen</b> foundation knowledge, basic concepts, guided application</span><span><b>Hard</b> multi-step reasoning and application</span><span><b>Extend</b> higher-order thinking and unfamiliar problems</span></div>
    <div class="legend" style="margin-top:8px"><span><b>Challenging</b> every question is a word problem</span><span><b>Mastery</b> two concepts in one question: find one thing first, then use it</span><span><b>Exceptional</b> long, multi-step questions with common traps, a little above the usual grade level</span></div>
    <p class="muted small" style="margin-top:8px">Challenging, Mastery and Exceptional questions are available for Mathematics. Other subjects do not have them yet, so a student can reach at most Advanced there.</p></div>
    <div class="card"><h3>Topic-wise distribution</h3><p class="muted small">Questions are spread evenly across the selected topics within each difficulty. Reports show every topic separately.</p></div>`
    : `<div class="card"><h3>Adaptive settings</h3><div class="grid g4" style="margin:10px 0">
    ${field('Minimum questions', inp('draft.adaptive.min', d.adaptive.min, 'number', 'min="3" max="60"'))}${field('Maximum questions', inp('draft.adaptive.max', d.adaptive.max, 'number', 'min="4" max="60"'))}
    ${field('Minimum per topic', inp('draft.adaptive.minPer', d.adaptive.minPer, 'number', 'min="1" max="10"'))}${field('Precision target (lower is stricter)', inp('draft.adaptive.sdTarget', d.adaptive.sdTarget, 'number', 'min="0.3" max="1" step="0.05"'))}
    ${field('Starting difficulty', `<select data-m="draft.adaptive.startB" data-t="num">${optList([{ v: -0.9, l: 'Gentle (near Strengthen)' }, { v: -0.5, l: 'Baseline (between Strengthen and Hard)' }, { v: 0, l: 'Standard (Hard)' }], d.adaptive.startB)}</select>`)}</div>
    ${chk('draft.adaptive.probe', d.adaptive.probe, 'Check prerequisite topics when a student keeps missing a topic')}
    <div class="banner info" style="margin-top:12px"><div><b>How the adaptive logic works</b><ul class="ul" style="margin-top:6px"><li>It estimates ability for each topic from every answer, weighting by question difficulty. One answer never decides the next step on its own.</li><li>The next topic is the one with the least evidence. Its question is chosen near the current estimate, and difficulty moves at most one step from the last question in that topic.</li><li>Two or more misses in a topic can trigger a prerequisite check (for example Addition before Multiplication).</li><li>It stops when every topic has enough evidence or the maximum is reached.</li><li>Response time is recorded as supporting information only. It never changes the estimate.</li></ul></div></div></div>`}`;
}
function bStep4(d) {
  const ad = d.model === 'adaptive';
  return `<div class="card"><h3>Delivery mode</h3><div class="grid g2" style="margin:10px 0">
    <label class="sub chk" style="cursor:pointer"><input type="radio" name="mode" data-m="draft.mode" data-r value="independent" ${d.mode === 'independent' ? 'checked' : ''}><span><b>Independent timed assessment</b><br><span class="muted small">Students take it on their own device with a countdown timer.</span></span></label>
    <label class="sub chk" style="cursor:pointer"><input type="radio" name="mode" data-m="draft.mode" data-r value="live" ${d.mode === 'live' ? 'checked' : ''}><span><b>Teacher-led live assessment</b><br><span class="muted small">One question at a time on a shared screen. The teacher controls the pace.</span></span></label></div>
    ${d.mode === 'live' && ad ? '<div class="banner warn">Live sessions show every student the same question, so a live assessment always uses a fixed set of questions chosen from your difficulty distribution. Switch to Fixed difficulty in the previous step, or use independent mode for adaptive testing.</div>' : ''}</div>
  <div class="card"><h3>Timing and navigation</h3><div class="grid g3" style="margin:10px 0">
    ${d.mode === 'independent' ? field('Total duration (minutes)', inp('draft.durationMin', d.durationMin, 'number', 'min="1" max="240"')) : ''}
    ${field(d.mode === 'live' ? 'Time per question in seconds' : 'Time allowed per question in seconds (0 for none)', inp('draft.perQuestionSec', d.perQuestionSec, 'number', 'min="0" max="600"'))}
    ${field('Attempts allowed', inp('draft.attempts', d.attempts, 'number', 'min="1" max="5"'))}</div>
    ${d.mode === 'independent' ? `<div class="grid g2">${chk('draft.allowBack', ad ? false : d.allowBack, 'Students can return to previous questions' + (ad ? ' (not available in adaptive tests)' : ''), true).replace('<input', ad ? '<input disabled' : '<input')}${chk('draft.allowSkip', d.allowSkip, 'Students can skip questions')}
    ${chk('draft.allowChange', ad ? false : d.allowChange, 'Students can change their answers' + (ad ? ' (not available in adaptive tests)' : '')).replace('<input', ad ? '<input disabled' : '<input')}${chk('draft.autoSubmit', d.autoSubmit, 'Submit automatically when time runs out')}</div>` : `<div class="grid g2">${chk('draft.autoAdvance', d.autoAdvance, 'Advance automatically when the question timer ends')}</div>`}</div>
  <div class="card"><h3>Randomisation, feedback and integrity</h3><div class="grid g2" style="margin-top:10px">
    ${chk('draft.shuffleQ', d.shuffleQ, 'Randomise question order (otherwise Strengthen, then Hard, then Extend)')}${chk('draft.shuffleOpts', d.shuffleOpts, 'Randomise answer options')}
    ${chk('draft.parentSupervision', d.parentSupervision, 'Require parent or guardian supervision confirmation (recommended for young learners)')}${chk('draft.fullscreen', d.fullscreen, 'Ask students to use full-screen mode where the device allows it')}
    ${field('Feedback to students', `<select data-m="draft.feedback">${optList([{ v: 'none', l: 'No answer review' }, { v: 'release', l: 'Answer review after the teacher releases results' }, { v: 'instant', l: 'Answer review right after submission' }], d.feedback)}</select>`)}
    ${field('Result visibility', `<select data-m="draft.resultVis">${optList([{ v: 'release', l: 'Teacher releases results' }, { v: 'instant', l: 'Show results right after submission' }], d.resultVis)}</select>`)}</div>
    <div class="banner" style="margin-top:12px">These safeguards reduce, but cannot prevent, outside help. A browser cannot see other devices or apps. Unusual events such as leaving full screen are recorded for the teacher and are never labelled as cheating. Extra time and larger text are set per student in the Students page.</div></div>`;
}
function validateDraft(d) {
  const err = [], warn = [], pool = poolFor(S.bank, d), counts = poolCounts(pool, d.topics);
  if (!d.title.trim()) err.push('Add a title.');
  if (!d.topics.length) err.push('Choose at least one topic.');
  if (d.application === 'course' && !d.courseId) warn.push('No course selected, so placement will use grade-level wording.');
  if (d.model === 'fixed' && sumBy(DIFF_KEYS, k => +d.dist[k] || 0) < 1) err.push('Set at least one question in the difficulty distribution.');
  if (d.topics.length && !pool.length) err.push('No approved questions exist for the chosen topics and grades. Add or generate questions in the Question bank, or change the selection.');
  if (d.model === 'fixed' && d.topics.length && pool.length && !(d.pinnedIds || []).length) err.push('No questions are selected. Check at least one row in "Matching questions in the bank" below.');
  counts.forEach(c => { if (!(c.S + c.H + c.E)) warn.push(`${c.topic} has no approved questions and will not be assessed.`); });
  if (d.model === 'fixed' && pool.length) { const fx = buildFixed(d, S.bank, 'v'); fx.shortages.forEach(s => warn.push(`Not enough ${DIFF[s.diff].name} questions: ${s.missing} fewer than requested will be used.`)); if (d.topics.length > sumBy(DIFF_KEYS, k => +d.dist[k] || 0)) warn.push('There are more topics than questions, so some topics will not appear.'); }
  if (d.model === 'adaptive') { counts.forEach(c => { if ((c.S + c.H + c.E) && (c.S + c.H + c.E) < d.adaptive.minPer + 2) warn.push(`${c.topic}: the pool (${c.S + c.H + c.E}) is small for adaptive testing. Add more questions at each level.`); });
    if (d.adaptive.min > d.adaptive.max) err.push('Minimum questions cannot be larger than the maximum.'); }
  if (d.mode === 'live' && d.model === 'adaptive') warn.push('Live sessions use a fixed question set.');
  if (!d.assignedTo.length) warn.push('No students are assigned yet. Assign students below or after publishing.');
  return { err, warn, counts, pool };
}
function bStep5(d) {
  const v = validateDraft(d), c = courseOf(d);
  let body = '';
  // A fixed-difficulty test's exact questions are now whatever is
  // checked in the "Matching questions in the bank" table shown on
  // every step (see draftPoolPreview) - every student gets that same
  // set, so there is no "one example draw" to show separately here.
  if (d.model === 'adaptive' && v.pool.length) {
    const th = U.simTheta == null ? 0 : U.simTheta, fake = { id: 'sim', name: 'Simulated student', grade: d.grades[0] || 5 };
    const at = simulateAttempt(d, S.bank, fake, { theta: th, speed: 1, skipP: 0 }, 'prev' + th + (U.pvSeed || 1), Date.now()), an = analyze(at, levels(), c);
    body = `<div class="card"><h3>Test the adaptive logic</h3><p class="muted small">Move the slider to simulate a student of a given ability and see how difficulty follows the answers. Nothing is saved.</p>
      <div class="row" style="margin:10px 0"><span class="small">Weaker</span><input type="range" min="-2" max="2" step="0.25" value="${th}" data-m="simTheta" data-t="num" data-r style="flex:1;min-width:160px;accent-color:var(--purple)" aria-label="Simulated ability"><span class="small">Stronger</span><span class="pill">${th > 0 ? '+' : ''}${th}</span><button class="btn sm ghost" data-a="redraw">Run again</button></div>
      ${pathChart(at.responses)}<p class="small muted" style="margin:6px 0 10px">${at.responses.length} questions · stopped: ${esc(at.stopReason)} · estimated overall level ${esc(lvName(an.overallIdx))}</p>
      <details class="sub"><summary>Why each question was chosen</summary><div class="tw"><table><thead><tr><th>#</th><th>Topic</th><th>Level</th><th>Reason</th></tr></thead><tbody>${at.trace.map(t => `<tr><td>${t.n}</td><td>${esc(t.topic)}${t.probe ? ' <span class="pill warn">prerequisite check</span>' : ''}</td><td>${DIFF[t.diff].name}</td><td>${esc(t.why)}</td></tr>`).join('')}</tbody></table></div></details></div>
      <div class="card"><h3>Question pool per topic</h3><div class="tw"><table><thead><tr><th>Topic</th><th class="num">Strengthen</th><th class="num">Hard</th><th class="num">Extend</th></tr></thead><tbody>${v.counts.map(x => `<tr><td>${esc(x.topic)}</td><td class="num">${x.S}</td><td class="num">${x.H}</td><td class="num">${x.E}</td></tr>`).join('')}</tbody></table></div></div>`;
  }
  const grade = d.grades;
  const candidates = S.students.filter(s => !grade.length || grade.includes(s.grade));
  return `<div class="card"><h3>Summary</h3><div class="tiles" style="margin-top:8px"><div class="tile"><b>${d.topics.length}</b><span>Topics</span></div><div class="tile"><b>${d.model === 'fixed' ? (d.pinnedIds || []).length : d.adaptive.min + '–' + d.adaptive.max}</b><span>Questions</span></div>
    <div class="tile"><b>${d.mode === 'live' ? (d.perQuestionSec || 30) + 's' : d.durationMin + ' min'}</b><span>${d.mode === 'live' ? 'Per question' : 'Duration'}</span></div><div class="tile"><b>${d.mode === 'live' ? 'Live' : 'Solo'}</b><span>Mode</span></div></div>
    <p class="small muted" style="margin-top:10px">${esc(d.purpose)} · ${c ? esc(c.name) : 'School assessment'} · ${d.grades.length ? 'Grade ' + d.grades.join(', ') : 'All grades'} · ${d.model === 'adaptive' ? 'Adaptive' : 'Fixed difficulty'} · ${d.parentSupervision ? 'Parent supervision required' : 'No parent supervision step'}</p></div>
    ${v.err.length ? `<div class="banner bad"><div><b>Fix before publishing</b><ul class="ul">${v.err.map(e => `<li>${esc(e)}</li>`).join('')}</ul></div></div>` : ''}${v.warn.length ? `<div class="banner warn"><div><b>Check these</b><ul class="ul">${v.warn.map(e => `<li>${esc(e)}</li>`).join('')}</ul></div></div>` : ''}${body}
    <div class="card"><div class="row sp"><h3>Assign to students</h3><div class="row"><button class="btn sm ghost" data-a="assignAll">Assign all listed</button><button class="btn sm ghost" data-a="assignNone">Clear</button></div></div>
      <div class="grid g3" style="margin-top:10px">${candidates.map(s => `<label class="chk"><input type="checkbox" data-a="tglAssign" data-id="${s.id}" ${d.assignedTo.includes(s.id) ? 'checked' : ''}><span>${esc(s.name)} <span class="muted small">Grade ${s.grade == null ? '—' : s.grade}</span></span></label>`).join('')}</div></div>
    <div class="row"><button class="btn ghost" data-a="previewRun" ${v.err.length ? 'disabled' : ''}>Preview as a student</button><button class="btn ghost" data-a="saveDraft">Save draft</button><button class="btn teal lg" data-a="publish" ${v.err.length ? 'disabled' : ''}>Publish assessment</button></div>`;
}
function pathChart(resps) {
  const W = 640, H = 190, L = 70, Rm = 14, T = 12, B = 28, n = Math.max(resps.length, 2), x = i => L + (W - L - Rm) * (i / (n - 1)), y = b => T + (H - T - B) * (1 - (b + 1.7) / 5.2);
  const marks = resps.map((r, i) => { const cx = x(i), cy = y(r.b); const f = r.status === 'correct' ? 'var(--ok)' : r.status === 'incorrect' ? 'var(--bad)' : 'var(--surface)'; const s = r.status === 'correct' || r.status === 'incorrect' ? 'var(--surface)' : 'var(--skip)';
    return `<circle cx="${cx}" cy="${cy}" r="5.5" fill="${f}" stroke="${s}" stroke-width="2"><title>Q${i + 1} ${esc(r.topic)} · ${DIFF[r.diff].name} · ${r.status}${r.probe ? ' · prerequisite check' : ''}</title></circle>`; }).join('');
  const line = resps.map((r, i) => (i ? 'L' : 'M') + x(i).toFixed(1) + ' ' + y(r.b).toFixed(1)).join(' ');
  return `<div class="tw"><svg class="svgchart" viewBox="0 0 ${W} ${H}" style="min-width:420px;width:100%;height:auto" role="img" aria-label="Question difficulty path, ${resps.length} questions">
    ${DIFF_KEYS.map(k => `<line x1="${L}" x2="${W - Rm}" y1="${y(DIFF[k].b)}" y2="${y(DIFF[k].b)}" stroke="var(--line)" stroke-dasharray="3 4"/><text x="${L - 8}" y="${y(DIFF[k].b) + 4}" text-anchor="end">${DIFF[k].name}</text>`).join('')}
    <path d="${line}" fill="none" stroke="var(--muted)" stroke-width="1.5" opacity=".6"/>${marks}
    <text x="${L}" y="${H - 8}">Question 1</text><text x="${W - Rm}" y="${H - 8}" text-anchor="end">Question ${resps.length}</text></svg></div>
    <div class="legend"><span><i style="background:var(--ok);border-radius:50%"></i>Correct</span><span><i style="background:var(--bad);border-radius:50%"></i>Incorrect</span><span><i style="border:2px solid var(--skip);border-radius:50%"></i>Skipped or not answered</span></div>`;
}
