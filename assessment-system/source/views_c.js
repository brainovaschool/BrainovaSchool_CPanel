/* ===================== STUDENT, PARENT, EXPORTS, INTEGRATION, SETTINGS ===================== */
const SUP_ITEMS = ['My child will work on the assessment independently.', 'No one will give my child answers, hints or other help during the assessment.', 'ChatGPT or any other AI answer tool will not be used.', 'My child will not use other devices, books or notes unless the teacher has allowed them.', 'The assessment will be taken in a quiet, suitable place without interruptions.'];
const supKey = (aid, sid) => aid + '|' + sid;
const supOf = (aid, sid) => S.supervision[supKey(aid, sid)];

function assignedFor(sid) { return S.assessments.filter(a => a.status === 'published' && a.assignedTo.includes(sid)); }
function stateFor(a, sid) { const at = attemptsOf(a.id, sid); return { at, left: Math.max(0, a.attempts - at.length) }; }

VIEWS.my = function () {
  const sid = U.studentId, st = studentById(sid), list = assignedFor(sid), ac = accomFor(sid);
  return `<div class="page-head"><div><h1>Hello, ${esc(st.name.split(' ')[0])}</h1><p>Your assigned assessments. Take your time, read each question carefully and do your own work.</p></div></div>
  ${ac.extraPct || ac.largeText ? `<div class="banner info">Your accommodations are active: ${ac.extraPct ? '+' + ac.extraPct + '% extra time' : ''}${ac.extraPct && ac.largeText ? ' and ' : ''}${ac.largeText ? 'larger text' : ''}.</div>` : ''}
  ${list.length ? `<div class="stack">${list.map(a => { const s = stateFor(a, sid), done = s.at.length > 0, sup = supOf(a.id, sid);
    return `<div class="card"><div class="row sp"><div><div class="row" style="margin-bottom:6px"><span class="pill pur">${esc(a.purpose)}</span>${a.mode === 'live' ? '<span class="pill">Live in class</span>' : `<span class="pill">${a.durationMin} minutes</span>`}${a.parentSupervision ? `<span class="pill ${sup ? 'ok' : 'warn'}">${sup ? 'Parent confirmed' : 'Parent confirmation needed'}</span>` : ''}</div><h3>${esc(a.title)}</h3>
      <p class="muted small">${esc(subjectsOf(a).join(', '))} · ${a.model === 'adaptive' ? 'Questions adjust to you' : sumBy(DIFF_KEYS, k => a.dist[k] || 0) + ' questions'}</p></div>
      <div class="row">${done ? '<span class="pill ok">Submitted</span>' : ''}${a.mode === 'live' ? '<span class="muted small">Your teacher will run this one in class.</span>' : s.left ? `<button class="btn teal" data-a="intro" data-id="${a.id}">${done ? 'Try again' : 'Open'}</button>` : '<span class="muted small">No attempts left</span>'}</div></div></div>`; }).join('')}</div>` : '<div class="empty">Nothing is assigned to you right now.</div>'}`;
};
VIEWS.hist = function () {
  const at = S.attempts.filter(a => a.studentId === U.studentId && !a.preview).sort((a, b) => b.finished - a.finished);
  return `<div class="page-head"><div><h1>History</h1><p>Your submitted assessments. Results appear after your teacher releases them.</p></div></div>
  ${at.length ? `<div class="stack">${at.map(a => `<div class="card row sp"><div><h3>${esc(a.title)}</h3><p class="muted small">${fmtDate(a.finished)} · ${fmtDur(a.durationMs)}</p></div><div class="row">${a.released ? `<span class="pill ok">Results ready</span><button class="btn sm" data-a="openAtt" data-id="${a.id}">View results</button>` : '<span class="pill">Waiting for your teacher</span>'}</div></div>`).join('')}</div>` : '<div class="empty">You have not submitted any assessments yet.</div>'}`;
};
VIEWS.intro = function () {
  const a = asmtById(U.params.id), sid = U.studentId, ac = accomFor(sid); if (!a) return '<div class="empty">Assessment not found.</div>';
  const sup = supOf(a.id, sid), ex = 1 + ac.extraPct / 100, ad = a.model === 'adaptive', perQ = a.perQuestionSec;
  const need = a.parentSupervision && !sup;
  const nq = ad ? 'The number of questions depends on your answers (about ' + a.adaptive.min + ' to ' + a.adaptive.max + ').' : sumBy(DIFF_KEYS, k => a.dist[k] || 0) + ' questions.';
  return `<div class="page-head"><div><h1>${esc(a.title)}</h1><p>${esc(a.purpose)} · ${esc(subjectsOf(a).join(', '))}</p></div><button class="btn ghost" data-a="go" data-v="my">Back</button></div>
  <div class="card"><h2>Before you start</h2><p style="white-space:pre-wrap;margin-bottom:12px">${esc(a.instructions)}</p><ul class="ul"><li>${esc(nq)}</li><li>Time limit: ${Math.round(a.durationMin * ex)} minutes${ac.extraPct ? ' (includes your extra time)' : ''}. The assessment is submitted automatically when time runs out.</li>${perQ ? `<li>Each question has its own timer of ${Math.round(perQ * ex)} seconds, and you cannot go back.</li>` : ''}
    <li>${ad ? 'You cannot go back or change an answer, because the next question depends on your answers.' : `${a.allowBack && !perQ ? 'You can go back to earlier questions' : 'You cannot go back to earlier questions'}${a.allowChange && !perQ ? ' and change your answers.' : '.'}`}</li><li>${a.allowSkip ? 'You can skip a question you are unsure about.' : 'Please answer every question.'}</li><li>Results are shared by your teacher.</li></ul></div>
  ${a.parentSupervision ? `<div class="card"><h2>Parent or guardian supervision</h2>${sup ? `<div class="banner ok">Confirmed by ${esc(sup.by)} on ${fmtDate(sup.at)}.</div>` : `<p class="muted" style="margin-bottom:10px">A parent or guardian needs to confirm each point before the assessment starts.</p><div class="stack">${SUP_ITEMS.map((t, i) => `<label class="chk"><input type="checkbox" class="supc" ${U.supTick && U.supTick[i] ? 'checked' : ''} data-a="supTick" data-i="${i}"><span>${esc(t)}</span></label>`).join('')}${field('Parent or guardian name', `<input type="text" id="supname" placeholder="Full name" value="${esc(U.supName || '')}" data-m="supName">`)}<div><button class="btn" data-a="supConfirm" data-id="${a.id}" data-s="${sid}">Confirm supervision</button></div></div>`}</div>` : ''}
  <div class="banner">This assessment uses timing${a.shuffleOpts ? ', shuffled answer options' : ''}${a.fullscreen ? ' and full-screen mode' : ''} to encourage independent work. These measures cannot stop outside help, so honest effort matters. If your screen locks or your connection drops, tell your teacher.</div>
  <div class="row"><button class="btn teal lg" data-a="beginRun" data-id="${a.id}" ${need ? 'disabled' : ''}>Start assessment</button>${need ? '<span class="muted small">Waiting for parent confirmation.</span>' : ''}</div>`;
};

VIEWS.parent = function () {
  const par = S.parents.find(p => p.id === U.parentId) || S.parents[0], kids = par.childIds.map(studentById).filter(Boolean);
  const kid = studentById(U.params.kid) || kids[0];
  const list = assignedFor(kid.id), at = S.attempts.filter(a => a.studentId === kid.id && !a.preview).sort((a, b) => b.finished - a.finished), rel = at.filter(a => a.released)[0];
  return `<div class="page-head"><div><h1>${esc(kid.name)}</h1><p>Grade ${kid.grade} · Student ID ${esc(kid.id)}</p></div>${kids.length > 1 ? `<div class="chips">${kids.map(k => `<button class="chip" data-a="kid" data-id="${k.id}" aria-pressed="${k.id === kid.id}">${esc(k.name.split(' ')[0])}</button>`).join('')}</div>` : ''}</div>
  <div class="card"><h2>Assessments</h2>${list.length ? `<div class="stack">${list.map(a => { const sup = supOf(a.id, kid.id), s = stateFor(a, kid.id);
    return `<div class="sub"><div class="row sp"><div><b>${esc(a.title)}</b><div class="muted small">${a.mode === 'live' ? 'Live in class' : a.durationMin + ' minutes'} · ${s.at.length ? 'Submitted' : 'Not started'}</div></div><div class="row">${a.parentSupervision ? (sup ? `<span class="pill ok">Supervision confirmed</span>` : `<button class="btn sm" data-a="parOpen" data-id="${a.id}">Confirm supervision</button>`) : '<span class="pill">No supervision needed</span>'}</div></div>
    ${U.parOpen === a.id && !sup ? `<div class="stack" style="margin-top:10px">${SUP_ITEMS.map((t, i) => `<label class="chk"><input type="checkbox" data-a="supTick" data-i="${i}" ${U.supTick && U.supTick[i] ? 'checked' : ''}><span>${esc(t)}</span></label>`).join('')}${field('Your name', `<input type="text" value="${esc(U.supName || par.name)}" data-m="supName">`)}<div><button class="btn" data-a="supConfirm" data-id="${a.id}" data-s="${kid.id}">Confirm</button></div></div>` : ''}</div>`; }).join('')}</div>` : '<p class="muted">No assessments are assigned.</p>'}</div>
  <div class="card"><h2>Results</h2>${at.length ? `<div class="stack">${at.map(a => `<div class="row sp sub"><div><b>${esc(a.title)}</b><div class="muted small">${fmtDate(a.finished)}</div></div>${a.released ? `<button class="btn sm" data-a="openAtt" data-id="${a.id}">View report</button>` : '<span class="pill">Waiting for the teacher to release</span>'}</div>`).join('')}</div>` : '<p class="muted">No submitted assessments yet.</p>'}</div>
  ${rel ? parentSummary(rel) : ''}`;
};
function parentSummary(att) {
  const an = AN(att), tp = an.topics.filter(t => !t.probe && t.idx != null), P = an.placement;
  return `<div class="card"><h2>What the latest results mean</h2><p class="muted small" style="margin-bottom:10px">${esc(att.title)} · ${fmtDate(att.finished)}</p><div class="grid g2"><div><h4>Going well</h4><ul class="ul small">${tp.filter(t => t.idx >= 2).map(t => `<li>${esc(t.topic)}: ${esc(lvName(t.idx))}</li>`).join('') || '<li>The report lists what has been understood well.</li>'}</ul></div><div><h4>Needs more practice</h4><ul class="ul small">${tp.filter(t => t.idx <= 1).map(t => `<li>${esc(t.topic)}: ${esc(lvName(t.idx))}</li>`).join('') || '<li>No topic needs urgent attention.</li>'}</ul></div></div>
  <div class="rec" style="margin-top:12px"><div class="eyebrow">Recommended next step</div><b>${esc(finalLevelText(att, an))}</b></div><p class="muted small" style="margin-top:8px">This is an estimate from one assessment. Your child’s teacher will confirm it.</p></div>`;
}

/* ---------- Exports ---------- */
function lmsPayload() {
  return { schema: 'brainova.assessment.v1', exported_at: new Date().toISOString(), students: S.students.map(s => ({ external_id: s.id, name: s.name, grade: s.grade, accommodations: { extra_time_pct: (s.accom || {}).extraPct || 0, large_text: !!(s.accom || {}).largeText } })),
    assessments: S.assessments.map(a => ({ uuid: a.id, title: a.title, purpose: a.purpose, application: a.application, course_id: a.courseId || null, grades: a.grades, model: a.model, mode: a.mode, topics: a.topics, status: a.status, config: { distribution: a.dist, adaptive: a.adaptive, duration_min: a.durationMin, per_question_sec: a.perQuestionSec } })),
    attempts: S.attempts.filter(a => !a.preview).map(a => { const an = AN(a); return { uuid: a.id, assessment_uuid: a.asmtId, student_external_id: a.studentId, started_at: new Date(a.started).toISOString(), finished_at: new Date(a.finished).toISOString(), duration_ms: a.durationMs, score: { marks: an.marks, max_marks: an.maxMarks, accuracy: an.accuracy },
      responses: a.responses.map(r => ({ seq: r.seq, question_id: r.qid, topic: r.topic, difficulty: r.diff, status: r.status, answer: r.answer, marks: r.marks, time_ms: r.timeMs, changed: r.changed })),
      topic_results: an.topics.map(t => ({ topic: t.topic, attempted: t.attempted, correct: t.correct, level: t.idx == null ? null : levels()[t.idx].name, confidence: t.conf, prerequisite_probe: t.probe })),
      recommendation: { overall_level: an.overallIdx == null ? null : lvName(an.overallIdx), system: an.placement.tier || an.placement.grade || null, final: a.review.override || null, finalised: !!a.review.finalized, notes: a.review.notes || '' }, released: !!a.released }; }) };
}
const EXPORTS = {
  attempts: () => toCSV([['attempt_id', 'student_id', 'student_name', 'grade', 'assessment_id', 'assessment', 'application', 'course', 'mode', 'model', 'started', 'finished', 'questions', 'attempted', 'correct', 'incorrect', 'skipped', 'not_reached', 'accuracy', 'marks', 'max_marks', 'total_time_s', 'avg_time_s', 'overall_level', 'consistent_difficulty', 'system_recommendation', 'final_recommendation', 'finalised', 'released', 'teacher_notes']]
    .concat(S.attempts.filter(a => !a.preview).map(a => { const an = AN(a), c = courseOf(a); return [a.id, a.studentId, a.studentName, a.grade, a.asmtId, a.title, a.application, c ? c.name : '', a.mode, a.model, new Date(a.started).toISOString(), new Date(a.finished).toISOString(), an.total, an.attempted, an.correct, an.incorrect, an.skipped, an.unanswered, an.accuracy == null ? '' : an.accuracy.toFixed(3), an.marks, an.maxMarks, Math.round(an.totalTimeMs / 1000), Math.round(an.avgTimeMs / 1000), an.overallIdx == null ? '' : lvName(an.overallIdx), an.consistent, an.placement.tier || an.placement.grade || '', a.review.override || '', a.review.finalized ? 'yes' : 'no', a.released ? 'yes' : 'no', a.review.notes || '']; }))),
  responses: () => toCSV([['attempt_id', 'student_id', 'assessment_id', 'sequence', 'question_id', 'subject', 'topic', 'difficulty', 'type', 'status', 'student_answer', 'correct_answer', 'marks', 'max_marks', 'time_s', 'answer_changed', 'visits', 'error_pattern', 'prerequisite_check']]
    .concat(S.attempts.filter(a => !a.preview).flatMap(a => a.responses.map(r => [a.id, a.studentId, a.asmtId, r.seq, r.qid, r.subject, r.topic, DIFF[r.diff].name, r.type, r.status, r.answer, r.correctAnswer, r.marks, r.maxMarks, (r.timeMs / 1000).toFixed(1), r.changed, r.visits, r.tag ? ERR[r.tag] : '', r.probe ? 'yes' : '']))))
  , topics: () => toCSV([['attempt_id', 'student_id', 'student_name', 'assessment_id', 'subject', 'topic', 'questions', 'attempted', 'correct', 'incorrect', 'skipped', 'accuracy', 'avg_time_s', 'estimate', 'level', 'evidence', 'prerequisite_check']]
    .concat(S.attempts.filter(a => !a.preview).flatMap(a => AN(a).topics.map(t => [a.id, a.studentId, a.studentName, a.asmtId, t.subject, t.topic, t.n, t.attempted, t.correct, t.incorrect, t.skipped, t.acc == null ? '' : t.acc.toFixed(3), Math.round(t.avgTimeMs / 1000), t.theta.toFixed(2), t.idx == null ? 'Needs assessment' : lvName(t.idx), t.conf, t.probe ? 'yes' : ''])))),
  students: () => toCSV([['student_id', 'name', 'grade', 'parent', 'extra_time_pct', 'large_text', 'attempts']].concat(S.students.map(s => { const p = S.parents.find(x => x.id === s.parentId); return [s.id, s.name, s.grade, p ? p.name : '', (s.accom || {}).extraPct || 0, (s.accom || {}).largeText ? 'yes' : 'no', S.attempts.filter(a => a.studentId === s.id && !a.preview).length]; }))),
  notes: () => toCSV([['attempt_id', 'student_id', 'student_name', 'assessment', 'teacher_notes', 'final_recommendation', 'finalised']].concat(S.attempts.filter(a => !a.preview && (a.review.notes || a.review.override)).map(a => [a.id, a.studentId, a.studentName, a.title, a.review.notes, a.review.override, a.review.finalized ? 'yes' : 'no']))),
  bank: () => toCSV([['id', 'subject', 'topic', 'grade_min', 'grade_max', 'difficulty', 'type', 'text', 'option_a', 'option_b', 'option_c', 'option_d', 'answer', 'explanation', 'objective', 'time_sec', 'marks', 'prerequisite', 'source', 'status']].concat(S.bank.map(q => {
    const o = q.options || [], ans = q.type === 'mcq' ? q.answer : q.type === 'tf' ? (q.answer === 't' ? 'true' : 'false') : q.type === 'num' ? q.answer : q.type === 'fill' ? q.accept.join('|') : q.type === 'short' ? q.keywords.join('|') : q.type === 'order' ? q.items.join('|') : q.type === 'match' ? q.pairs.map(p => p.join('=')).join('|') : '';
    return [q.id, q.subject, q.topic, q.grades ? q.grades[0] : '', q.grades ? q.grades[1] : '', q.diff, q.type, (q.code ? q.text + '\n' + q.code : q.text), ...(q.type === 'mcq' ? [0, 1, 2, 3].map(i => o[i] ? o[i].t : '') : ['', '', '', '']), ans, q.explanation, q.objective, q.time, q.marks, q.prereq || '', q.source, q.status]; })))
};
VIEWS.export = function () {
  const n = S.attempts.filter(a => !a.preview).length;
  const items = [['attempts', 'Student performance summary', `${n} attempts: scores, levels, recommendations, teacher notes`, 'brainova-attempts.csv'], ['responses', 'Question-level responses', 'Every answer with timing, marks, changes and error pattern', 'brainova-responses.csv'], ['topics', 'Topic-wise performance history', 'Per attempt and topic: accuracy, estimate and level', 'brainova-topic-performance.csv'],
    ['students', 'Students and accommodations', `${S.students.length} students`, 'brainova-students.csv'], ['notes', 'Teacher observations and final recommendations', 'Notes and overrides', 'brainova-teacher-notes.csv'], ['bank', 'Question bank', `${S.bank.length} questions in import-ready format`, 'brainova-question-bank.csv']];
  return `<div class="page-head"><div><h1>Exports</h1><p>CSV files open directly in Excel and Google Sheets. IDs are consistent across files so records can be linked to the Brainova LMS.</p></div></div>
  <div class="grid g3">${items.map(i => `<div class="card stack"><div><h3>${i[1]}</h3><p class="muted small">${i[2]}</p></div><div><button class="btn" data-a="exp" data-k="${i[0]}" data-f="${i[3]}">Download CSV</button></div></div>`).join('')}</div>
  <div class="card"><h2>Bulk export</h2><div class="row"><button class="btn teal" data-a="expBulk">Download everything (all CSV files)</button><button class="btn ghost" data-a="expLms">LMS import file (JSON)</button><button class="btn ghost" data-a="expAssess">Assessment configurations (JSON)</button></div><p class="muted small" style="margin-top:8px">Each downloaded file asks for your confirmation. The bulk option offers the files one after another.</p></div>
  <div class="card"><h2>What each report includes</h2><p class="muted small" style="margin-bottom:10px">Open a student’s report in Results to view and download the Administrator, Teacher, Parent or Student version.${U.role === 'admin' ? ' Change the defaults in Settings.' : ''}</p>${sharingTable(false)}</div>
  <div class="card"><h2>Printable reports</h2><p class="muted">Open any student’s report in Results and choose “Download report (HTML)”. Open the file and use Print, then choose Save as PDF. Server-generated PDFs are part of the Laravel integration (see LMS integration).</p></div>`;
};

/* ---------- Integration ---------- */
const ENDPOINTS = [
  ['GET', '/api/lms/v1/students?grade=&updated_since=', 'LMS → Assessment', 'Student accounts and IDs (id, name, grade, status, parent_ids)'], ['GET', '/api/lms/v1/teachers', 'LMS → Assessment', 'Teacher accounts, roles and the classes they teach'], ['GET', '/api/lms/v1/parents?student_id=', 'LMS → Assessment', 'Parent accounts linked to a student'],
  ['GET', '/api/lms/v1/subjects, /courses, /grades', 'LMS → Assessment', 'Subject, course and grade records (id, name, code, grade_range)'], ['GET', '/api/lms/v1/syllabus?subject_id=', 'LMS → Assessment', 'Units, modules, terms, topics and learning objectives'],
  ['POST', '/api/lms/v1/assessment-assignments', 'Assessment → LMS', 'Assign an assessment to students, classes or applicants (with due date)'], ['GET', '/api/assessments/v1/assignments?student_id=', 'Assessment → LMS', 'Assigned assessments for a student account'],
  ['POST', '/api/assessments/v1/attempts', 'Browser → Assessment', 'Start an attempt. Server picks questions and returns the first item'], ['PUT', '/api/assessments/v1/attempts/{id}/responses', 'Browser → Assessment', 'Save answers with time spent. Adaptive attempts return the next item'],
  ['POST', '/api/assessments/v1/attempts/{id}/submit', 'Browser → Assessment', 'Submit, score and compute the report'], ['POST', '/api/assessments/v1/attempts/{id}/events', 'Browser → Assessment', 'Log interruptions (tab hidden, full screen exited, reconnects)'],
  ['GET', '/api/assessments/v1/attempts/{id}/report', 'Assessment → LMS', 'Diagnostic report as JSON (and PDF with ?format=pdf)'], ['POST', '/api/lms/v1/students/{id}/placements', 'Assessment → LMS', 'Write the teacher-approved recommended course or level to the student profile'],
  ['GET/POST/PUT', '/api/assessments/v1/questions', 'Teacher → Assessment', 'Question bank create, update, approve and import'], ['POST', '/api/assessments/v1/live-sessions', 'Teacher → Assessment', 'Create live sessions. Student devices join over WebSockets (Laravel Reverb)'], ['WEBHOOK', 'assessment.completed', 'Assessment → LMS', 'Notify the LMS when a report is ready']
];
const FIELD_MAP = [['students.id', 'student_external_id', 'Existing Brainova student ID, never re-created'], ['users.role', 'role', 'admin, teacher, student, parent (maps to Laravel roles/permissions)'], ['courses.id', 'course_id', 'Existing course record'], ['grades.id', 'grade', '1–10'], ['assessment_attempts.id', 'attempt uuid', 'Stable ID for linking reports'], ['assessment_responses.*', 'responses[]', 'Per-question record with time and marks'], ['assessment_topic_results.*', 'topic_results[]', 'Level, confidence and evidence per topic'], ['student_placements.*', 'recommendation', 'System suggestion, teacher override, finalised flag']];
const LARAVEL_KIT = `# Brainova Assessment: Laravel integration kit

Targets the existing Brainova stack: Laravel, Blade, Bootstrap, jQuery and the Repository Pattern.
Nothing here assumes access to the production database. Run it in staging first.

## 1. Tables (migrations)

\`\`\`php
Schema::create('assessment_questions', function (Blueprint $t) {
    $t->uuid('id')->primary(); $t->foreignId('subject_id'); $t->unsignedBigInteger('topic_id'); $t->string('objective')->nullable();
    $t->unsignedTinyInteger('grade_min')->nullable(); $t->unsignedTinyInteger('grade_max')->nullable();
    $t->enum('difficulty', ['strengthen','hard','extend']); $t->string('type'); $t->longText('body'); $t->json('options')->nullable(); $t->json('answer_key');
    $t->text('explanation')->nullable(); $t->text('marking')->nullable(); $t->unsignedSmallInteger('est_seconds')->default(45); $t->unsignedTinyInteger('marks')->default(1);
    $t->string('prerequisite_topic')->nullable(); $t->string('source')->nullable(); $t->enum('status', ['draft','approved'])->default('draft'); $t->decimal('irt_b', 4, 2)->default(0); $t->timestamps();
});
Schema::create('assessments', function (Blueprint $t) {
    $t->uuid('id')->primary(); $t->string('title'); $t->string('purpose'); $t->enum('application', ['school','course']); $t->foreignId('course_id')->nullable();
    $t->json('grades'); $t->json('topics'); $t->enum('model', ['fixed','adaptive']); $t->enum('mode', ['independent','live']); $t->json('config'); $t->enum('status', ['draft','published','closed'])->default('draft');
    $t->foreignId('created_by'); $t->timestamps();
});
Schema::create('assessment_assignments', function (Blueprint $t) {
    $t->id(); $t->uuid('assessment_id'); $t->foreignId('student_id'); $t->timestamp('due_at')->nullable(); $t->boolean('parent_confirmed')->default(false); $t->string('parent_name')->nullable(); $t->timestamp('parent_confirmed_at')->nullable();
    $t->unique(['assessment_id','student_id']);
});
Schema::create('assessment_attempts', function (Blueprint $t) {
    $t->uuid('id')->primary(); $t->uuid('assessment_id'); $t->foreignId('student_id'); $t->unsignedTinyInteger('attempt_no'); $t->timestamp('started_at'); $t->timestamp('submitted_at')->nullable();
    $t->unsignedInteger('duration_ms')->nullable(); $t->json('accommodations')->nullable(); $t->json('adaptive_trace')->nullable(); $t->boolean('released')->default(false); $t->timestamps();
});
Schema::create('assessment_responses', function (Blueprint $t) {
    $t->id(); $t->uuid('attempt_id'); $t->uuid('question_id'); $t->unsignedSmallInteger('seq'); $t->json('question_snapshot'); $t->json('answer')->nullable();
    $t->enum('status', ['correct','incorrect','skipped','unanswered','pending']); $t->decimal('marks', 5, 2)->default(0); $t->unsignedInteger('time_ms')->default(0); $t->unsignedSmallInteger('changes')->default(0); $t->string('error_pattern')->nullable();
});
Schema::create('assessment_topic_results', function (Blueprint $t) {
    $t->id(); $t->uuid('attempt_id'); $t->string('topic'); $t->unsignedSmallInteger('attempted'); $t->unsignedSmallInteger('correct'); $t->decimal('estimate', 5, 2); $t->decimal('sd', 5, 2);
    $t->unsignedTinyInteger('level_index')->nullable(); $t->string('confidence');
});
Schema::create('assessment_placements', function (Blueprint $t) {
    $t->id(); $t->uuid('attempt_id'); $t->foreignId('student_id'); $t->string('system_recommendation'); $t->string('final_recommendation')->nullable(); $t->text('teacher_notes')->nullable();
    $t->boolean('finalised')->default(false); $t->foreignId('finalised_by')->nullable(); $t->timestamp('finalised_at')->nullable();
});
Schema::create('assessment_events', function (Blueprint $t) { $t->id(); $t->uuid('attempt_id'); $t->string('type'); $t->string('note')->nullable(); $t->timestamp('occurred_at'); });
\`\`\`

## 2. Routes (routes/api.php)

\`\`\`php
Route::prefix('assessments/v1')->middleware(['auth:sanctum'])->group(function () {
    Route::get('assignments', [AssignmentController::class, 'index']);
    Route::post('attempts', [AttemptController::class, 'start'])->middleware('can:take,assessment');
    Route::put('attempts/{attempt}/responses', [AttemptController::class, 'saveResponses']);
    Route::post('attempts/{attempt}/submit', [AttemptController::class, 'submit']);
    Route::post('attempts/{attempt}/events', [AttemptController::class, 'event']);
    Route::get('attempts/{attempt}/report', [ReportController::class, 'show']);
    Route::middleware('role:teacher|admin')->group(function () {
        Route::apiResource('assessments', AssessmentController::class);
        Route::apiResource('questions', QuestionController::class);
        Route::post('assessments/{assessment}/publish', [AssessmentController::class, 'publish']);
        Route::post('attempts/{attempt}/release', [ReportController::class, 'release']);
        Route::put('attempts/{attempt}/placement', [PlacementController::class, 'finalise']);
        Route::post('live-sessions', [LiveSessionController::class, 'store']);
    });
});
\`\`\`

## 3. Repository pattern

\`\`\`php
interface AttemptRepositoryInterface {
    public function start(Assessment $a, int $studentId): Attempt;
    public function saveResponses(Attempt $at, array $responses): Attempt;
    public function submit(Attempt $at): Attempt;
    public function forStudent(int $studentId): Collection;
}
final class EloquentAttemptRepository implements AttemptRepositoryInterface { /* Eloquent queries; no business rules here */ }
// Bind in AppServiceProvider: $this->app->bind(AttemptRepositoryInterface::class, EloquentAttemptRepository::class);
\`\`\`

Keep scoring, adaptive selection and analysis in services (\`AdaptiveEngine\`, \`ScoringService\`, \`PlacementService\`). Port the logic in this app's engine one-to-one so results match: a 3-parameter logistic model with slope 1.2, EAP estimates on a -3 to 3 grid, a topic prior centred on the overall estimate, step limits and prerequisite checks.

## 4. Security and roles

- Authenticate with the existing Laravel guard (Sanctum or Passport). Reuse the LMS users table; never duplicate accounts.
- Roles: admin, teacher, student, parent via spatie/laravel-permission. Policies: students read only their own attempts and only when \`released\`; parents read linked children; teachers read their classes.
- Never send answer keys to the browser during an attempt. Return the presented question without \`answer_key\`; score on the server.
- Store attempts on the server after every answer so a refresh or lost connection does not lose work.
- Rate-limit the attempt endpoints, log access to reports, and keep personal data to what is needed. No facial recognition or invasive monitoring.

## 5. Front end (Blade, Bootstrap, jQuery)

Embed the assessment screens as Blade views. The browser code calls the endpoints above with \`$.ajax\`, sending the CSRF or bearer token. Live sessions use Laravel Reverb (or Pusher) channels: \`live-session.{id}\` for question events and a private channel per student for answers.

## 6. PDFs and exports

Render the report Blade view with barryvdh/laravel-dompdf (or Snappy) for \`?format=pdf\`. CSV exports use \`fastexcel\` or \`maatwebsite/excel\` with the same columns as this app's exports.

## 7. AI question generation

Call the model from a queued job on the server (never from the browser). Save results as \`draft\` questions. A teacher must approve each one before it can appear in an assessment.
`;
VIEWS.integration = function () {
  const l = S.settings.lms, first = S.attempts.find(a => !a.preview), sample = first ? JSON.stringify(lmsPayload().attempts.filter(a => a.uuid === first.id)[0], null, 2).slice(0, 2600) : '{}';
  return `<div class="page-head"><div><h1>LMS integration</h1><p>Connect assessment data to the Brainova Laravel LMS. This preview keeps data in the browser only, so nothing here is connected yet.</p></div></div>
  <div class="banner ${l.status === 'Connected' ? 'ok' : 'warn'}"><div><b>Connection status: ${esc(l.status)}</b><br>No call has been made to a production database. Enter the staging URL and an API token, then test the connection from a page served on the Brainova domain. This hosted preview blocks requests to other sites, so the test below reports that honestly.</div></div>
  <div class="card"><h2>Connection settings</h2><div class="grid g3">${field('LMS base URL', inp('lms.baseUrl', l.baseUrl, 'url', 'placeholder="https://lms.brainova.example"'))}${field('Tenant or school code', inp('lms.tenant', l.tenant))}${field('API token (Sanctum or Passport)', inp('lms.token', l.token, 'password', 'autocomplete="off"'))}</div>
    <div class="row" style="margin-top:12px"><button class="btn" data-a="lmsSave">Save settings</button><button class="btn ghost" data-a="lmsTest">Test connection</button></div><p class="muted small" style="margin-top:8px">Tokens saved here stay in this browser. In production, keep tokens server-side in the Laravel .env file.</p></div>
  <div class="card"><h2>Endpoints the LMS and assessment service need</h2><p class="muted small" style="margin-bottom:8px">If the existing site does not expose these yet, they are the ones to build.</p><div class="tw"><table><thead><tr><th>Method</th><th>Path</th><th>Direction</th><th>Purpose</th></tr></thead><tbody>${ENDPOINTS.map(e => `<tr><td class="mono small nowrap">${e[0]}</td><td class="mono small">${esc(e[1])}</td><td class="small nowrap">${e[2]}</td><td>${esc(e[3])}</td></tr>`).join('')}</tbody></table></div></div>
  <div class="grid g2"><div class="card"><h2>Field mapping</h2><div class="tw"><table><thead><tr><th>LMS</th><th>Assessment</th><th>Notes</th></tr></thead><tbody>${FIELD_MAP.map(r => `<tr><td class="mono small">${r[0]}</td><td class="mono small">${r[1]}</td><td>${esc(r[2])}</td></tr>`).join('')}</tbody></table></div></div>
  <div class="card"><h2>Demonstration and production</h2><div class="tw"><table><thead><tr><th>Capability</th><th>This preview</th><th>Production needs</th></tr></thead><tbody>
    ${[['Storage', 'Browser storage with JSON backup', 'MySQL tables via Laravel repositories'], ['Sign-in and roles', 'Role switcher', 'LMS sign-in with permissions and policies'], ['Live class', 'One screen with simulated classmates', 'WebSockets (Reverb) so real devices join'], ['Adaptive engine', 'Runs in the browser', 'Same logic on the server, answer keys hidden'], ['AI questions', 'Drafts through Claude when available', 'Server-side queued job with teacher approval'], ['PDF reports', 'Print the downloaded HTML', 'DomPDF or Snappy on the server'], ['Integrity', 'Timing, shuffling, event log', 'Plus server-side timers and audit logs'], ['LMS data', 'Seeded sample students', 'Real accounts through the endpoints above']].map(r => `<tr><td><b>${r[0]}</b></td><td>${r[1]}</td><td>${r[2]}</td></tr>`).join('')}</tbody></table></div></div></div>
  <div class="card"><div class="row sp"><h2>Sample payload (one attempt, LMS format)</h2><div class="row"><button class="btn ghost" data-a="expLms">Download LMS import file</button><button class="btn" data-a="dlKit">Download Laravel kit</button></div></div><pre class="code" style="max-height:340px">${esc(sample)}${sample.length >= 2600 ? '\n…' : ''}</pre></div>`;
};

/* ---------- Settings ---------- */
function sharingTable(editable) {
  return `<div class="tw"><table><thead><tr><th>Detail</th><th>Administrator</th><th>Teacher</th><th>Parent</th><th>Student</th></tr></thead><tbody>${SHARE_SECTIONS.map(x => `<tr><td>${esc(x[1])}</td><td><span class="pill ok">Yes</span></td>${['teacher', 'parent', 'student'].map(a => { const on = !!sharingFor(a)[x[0]]; return `<td>${editable ? `<label class="chk"><input type="checkbox" data-a="shareTgl" data-aud="${a}" data-k="${x[0]}" ${on ? 'checked' : ''} aria-label="${esc(x[1])} for ${AUD_NAME[a]}"><span>${on ? 'Shared' : 'Hidden'}</span></label>` : (on ? '<span class="pill ok">Yes</span>' : '<span class="muted">Hidden</span>')}</td>`; }).join('')}</tr>`).join('')}</tbody></table></div>`;
}
const RBAC = [['Create and edit assessments', 1, 1, 0, 0], ['Manage and approve questions', 1, 1, 0, 0], ['Run live sessions', 1, 1, 0, 0], ['View all students’ results', 1, 1, 0, 0], ['Release results and finalise recommendations', 1, 1, 0, 0], ['Export data', 1, 1, 0, 0], ['Configure the Google Sheets backend, LMS connection and proficiency levels', 1, 0, 0, 0], ['Take assigned assessments', 0, 0, 1, 0], ['View own results after release', 0, 0, 1, 0], ['Confirm supervision and view a child’s released results', 0, 0, 0, 1]];
VIEWS.settings = function () {
  if (U.role !== 'admin') return '<div class="empty">Settings are available to administrators. Switch to the Administrator role.</div>';
  const gcfg = (window.BrainovaAPI && window.BrainovaAPI.getConfig()) || { url: '', token: '' };
  return `<div class="page-head"><div><h1>Settings</h1></div></div>
  <div class="card"><h2>Google Sheets backend</h2><p class="muted small" style="margin-bottom:10px">Connects this app's data to your Google Sheet. Create the Sheet and deploy the Apps Script first (see the setup guide), then put the same secret token in both places: the script's Project Settings &rarr; Script Properties on the Google side, and the field below here. The token stays only in this browser - it is never written into any file this app ships.</p>
    <div class="grid g2">${field('Web App URL', `<input type="url" id="gUrl" value="${esc(gcfg.url)}" placeholder="https://script.google.com/macros/s/.../exec">`)}${field('Shared token', `<input type="password" id="gTok" value="${esc(gcfg.token)}" autocomplete="off">`)}</div>
    <div class="row" style="margin-top:12px"><button class="btn" data-a="gcfgSave">Save</button><button class="btn ghost" data-a="gcfgTest">Test connection</button><span class="pill ${gcfg.url ? 'ok' : ''}">${gcfg.url ? 'Configured' : 'Not configured - data stays in this browser only'}</span>${window.BrainovaAPI && window.BrainovaAPI.pendingCount() ? `<span class="pill warn">${window.BrainovaAPI.pendingCount()} change(s) waiting to sync</span>` : ''}</div></div>
  <div class="card"><h2>Proficiency levels</h2><p class="muted small" style="margin-bottom:10px">Rename the levels and set where each begins on the ability scale (about −2 to +2). Higher means stronger.</p><div class="grid g3">${(U.lvDraft || levels()).map((l, i) => `<div class="sub stack"><span class="lv lv${i}" style="align-self:flex-start">Level ${i + 1}</span>${field('Name', inp('lvl.' + i + '.name', l.name))}${i ? field('Starts at', inp('lvl.' + i + '.min', l.min, 'number', 'step="0.05"')) : '<span class="muted small">Starts at the bottom of the scale</span>'}</div>`).join('')}</div>
    ${U.lvErr ? `<div class="banner bad" style="margin-top:10px">${esc(U.lvErr)}</div>` : ''}<div class="row" style="margin-top:12px"><button class="btn" data-a="lvSave">Apply levels</button><button class="btn ghost" data-a="lvReset">Restore defaults</button></div></div>
  <div class="card"><h2>Report sharing</h2><p class="muted small" style="margin-bottom:10px">Choose which details each audience receives in reports and exports. The administrator version always includes everything.</p>${sharingTable(true)}
    <div class="banner info" style="margin-top:12px"><div><b>Why the defaults are set this way</b><ul class="ul" style="margin-top:6px"><li><b>Teachers</b> get everything they need to act: scores, topic levels, error patterns, timing, answers, the adaptive log and the session record.</li><li><b>Parents</b> get a clear picture of their child: score, strengths, learning gaps, topic levels, the recommended starting point, next steps and the teacher’s note.</li><li>Parents do not receive per-question answers, timing, error-pattern labels or session logs by default. These can be misread out of context and can undermine the assessment if questions are reused.</li><li><b>Students</b> see the same as parents, plus the question review, so they can learn from mistakes once results are released.</li></ul></div></div>
    <div class="row" style="margin-top:12px"><button class="btn ghost" data-a="shareReset">Restore defaults</button></div></div>
  <div class="card"><h2>Roles and permissions</h2><div class="tw"><table><thead><tr><th>Capability</th><th>Admin</th><th>Teacher</th><th>Student</th><th>Parent</th></tr></thead><tbody>${RBAC.map(r => `<tr><td>${r[0]}</td>${r.slice(1).map(v => `<td>${v ? '<span class="pill ok">Yes</span>' : '<span class="muted">—</span>'}</td>`).join('')}</tr>`).join('')}</tbody></table></div></div>
  <div class="card"><h2>Data</h2><div class="row"><button class="btn ghost" data-a="backup">Download backup (JSON)</button><label class="btn ghost" for="restore" style="cursor:pointer">Restore backup</label><input id="restore" type="file" accept=".json,application/json" hidden data-a="restore"><button class="btn ghost" data-a="resetSample">${U.cfm === 'reset' ? 'Click again to confirm reset' : 'Reset to sample data'}</button>${U.lms ? `<button class="btn ghost" data-a="removeDemoStudents">${U.cfm === 'demo' ? 'Click again to remove the demo students' : 'Remove demo students (keep my LMS roster)'}</button>` : ''}<button class="btn danger" data-a="clearData">${U.cfm === 'clear' ? 'Click again to erase students and results' : 'Clear sample students and results'}</button></div>
    <p class="muted small" style="margin-top:8px">Clearing keeps the question bank and removes sample students, parents, assessments and attempts.${U.lms ? ' "Remove demo students" is narrower - it only removes the built-in sample names (Ayesha Khan and the rest), never anyone imported from your LMS.' : ''}</p></div>`;
};
