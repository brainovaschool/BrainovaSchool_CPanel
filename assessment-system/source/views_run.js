/* ===================== STUDENT RUNNER (independent timed) ===================== */
function qBody(q, raw, opts = {}) {
  const pad = (opts.cls || '');
  let h = '';
  if (q.passage) h += `<div class="passage">${esc(q.passage)}</div>`;
  h += `<div class="qtext ${pad}">${esc(q.text)}</div>`;
  if (q.code) h += `<pre class="code">${esc(q.code)}</pre>`;
  if (q.image) h += `<img class="qimg" src="${esc(q.image)}" alt="Diagram for the question">`;
  return h;
}
function answerInput(q, raw) {
  switch (q.type) {
    case 'mcq': case 'tf': return `<div class="opts" role="radiogroup" aria-label="Answer options">${q.options.map((o, i) => `<button class="opt" role="radio" aria-checked="${raw === o.id}" data-a="pick" data-v="${o.id}"><span class="ltr">${q.type === 'tf' ? (o.id === 't' ? 'T' : 'F') : 'ABCD'[i]}</span><span>${esc(o.t)}</span></button>`).join('')}</div>`;
    case 'num': return `<div style="margin-top:16px"><input class="answer-in" id="ans" type="text" inputmode="decimal" autocomplete="off" placeholder="Type your answer" value="${esc(raw || '')}" data-a="typed" aria-label="Your answer"></div>`;
    case 'fill': return `<div style="margin-top:16px"><input class="answer-in" id="ans" type="text" autocomplete="off" placeholder="Type your answer" value="${esc(raw || '')}" data-a="typed" aria-label="Your answer"></div>`;
    case 'short': case 'manual': return `<div style="margin-top:16px"><textarea id="ans" style="min-height:120px;font-size:16px" placeholder="Write your answer" data-a="typed" aria-label="Your answer">${esc(raw || '')}</textarea></div>`;
    case 'order': { const r = Array.isArray(raw) ? raw : []; return `<p class="muted small" style="margin-top:12px">Tap the items in the correct order.</p><div class="ordl">${q.shown.map(it => { const n = r.indexOf(it); return `<button class="opt" role="checkbox" aria-checked="${n >= 0}" data-a="ord" data-v="${esc(it)}"><span class="ltr">${n >= 0 ? n + 1 : ''}</span><span>${esc(it)}</span></button>`; }).join('')}</div><button class="btn sm ghost" style="margin-top:8px" data-a="ordClear">Clear order</button>`; }
    case 'match': { const r = Array.isArray(raw) ? raw : []; return `<div class="stack" style="margin-top:14px">${q.pairs.map((p, i) => `<div class="row" style="flex-wrap:nowrap"><b style="width:38%;min-width:90px">${esc(p[0])}</b><select data-a="mat" data-i="${i}" aria-label="Match for ${esc(p[0])}"><option value="">Choose…</option>${q.rights.map(x => `<option ${r[i] === x ? 'selected' : ''} value="${esc(x)}">${esc(x)}</option>`).join('')}</select></div>`).join('')}</div>`; }
  }
  return '';
}
function startRun(a, studentId, preview) {
  const st = studentById(studentId) || S.students[0], R = rng(uid('run')), seed = uid('seed'), ac = accomFor(st.id), ex = 1 + ac.extraPct / 100;
  let items = [], ad = null;
  if (a.model === 'adaptive') { ad = new Adaptive(a, S.bank, seed); if (ad.done()) { toast('No approved questions are available for this assessment yet.'); return; } const q = ad.next(); items.push(Object.assign(present(q, R, a.shuffleOpts), { _probe: !!q._probe })); }
  else { const fx = buildFixed(a, S.bank, seed); items = fx.items.map(q => present(q, R, a.shuffleOpts)); if (!items.length) { toast('No approved questions are available for this assessment yet.'); return; } }
  const now = Date.now(), perQ = a.perQuestionSec ? a.perQuestionSec * 1000 * ex : 0;
  U.run = { a, st, preview: !!preview, seed, R, ad, items, resp: items.map(() => ({ raw: null, ms: 0, visits: 0, changed: 0, timedOut: false })), idx: 0, started: now, deadline: a.durationMin > 0 ? now + a.durationMin * 60000 * ex : null, perQ, qStart: now, phase: 'q', events: [], doneResp: [], large: ac.largeText, att: null, attId: uid('att') };
  U.run.resp[0].visits = 1;
  if (a.fullscreen) { try { const p = document.documentElement.requestFullscreen && document.documentElement.requestFullscreen(); if (p && p.catch) p.catch(() => { }); } catch (e) { } }
  voiceStart();
  render(); scrollTop();
}
function runLog(type, note) { if (U.run && U.run.phase === 'q') U.run.events.push({ t: Date.now(), type, note }); }
document.addEventListener('visibilitychange', () => { if (!U.run || U.run.phase !== 'q') return; if (document.hidden) U.run.hiddenAt = Date.now(); else if (U.run.hiddenAt) { runLog('tab-hidden', `Page was hidden for ${Math.round((Date.now() - U.run.hiddenAt) / 1000)} s`); U.run.hiddenAt = 0; } });
document.addEventListener('fullscreenchange', () => { if (U.run && U.run.phase === 'q' && U.run.a.fullscreen && !document.fullscreenElement) runLog('fullscreen-exit', 'Left full-screen mode'); });
window.addEventListener('offline', () => runLog('offline', 'Connection lost'));

function curQ() { return U.run.items[U.run.idx]; }
function flushTime() { const r = U.run; r.resp[r.idx].ms += Date.now() - r.qStart; r.qStart = Date.now(); }
function setAnswer(raw) { const r = U.run, e = r.resp[r.idx]; const was = e.raw; if (!isBlank(was) && JSON.stringify(was) !== JSON.stringify(raw)) e.changed++; e.raw = raw; }
function runView() {
  const r = U.run, a = r.a, q = curQ(), n = r.items.length, ad = a.model === 'adaptive', free = !ad && a.allowBack && !r.perQ, total = ad ? a.adaptive.max : n;
  if (r.phase === 'done') return doneView();
  const head = `<div class="run-head"><div><b>${esc(a.title)}</b>${r.preview ? ' <span class="pill warn">Preview, not saved</span>' : ''}<div class="small" style="color:#C9D3E8">${esc(r.st.name)}</div></div><div class="row" style="gap:18px">${r.perQ && r.phase === 'q' ? `<div><div class="small" style="color:#C9D3E8">This question</div><div class="countdown"><i id="qbar" style="width:100%"></i></div></div>` : ''}${r.deadline ? `<div class="right"><div class="small" style="color:#C9D3E8">Time left</div><div class="timer" id="tmr">${clock(r.deadline - Date.now())}</div></div>` : ''}${r.preview ? '<button class="btn sm ghost" style="color:#fff;border-color:rgba(255,255,255,.4)" data-a="exitRun">Exit preview</button>' : ''}</div></div>`;
  if (r.phase === 'review') {
    const un = r.items.map((it, i) => ({ it, i, e: r.resp[i] })).filter(x => isBlank(x.e.raw));
    return `<div class="runner-wrap ${r.large ? 'large' : ''}">${head}<div class="run-body"><div class="qcard"><h2>Review before you submit</h2><p class="muted" style="margin:6px 0 12px">${un.length ? `${un.length} question${un.length > 1 ? 's have' : ' has'} no answer.` : 'Every question has an answer.'}</p>
      <div class="dots">${r.items.map((it, i) => `<button class="${isBlank(r.resp[i].raw) ? '' : 'ans'}" ${free ? `data-a="jump" data-i="${i}"` : 'disabled'} aria-label="Question ${i + 1} ${isBlank(r.resp[i].raw) ? 'unanswered' : 'answered'}">${i + 1}</button>`).join('')}</div></div>
      <div class="row sp">${free ? '<button class="btn ghost" data-a="backToQ">Back to questions</button>' : '<span></span>'}<button class="btn teal lg" data-a="submitRun">Submit assessment</button></div></div></div>`;
  }
  const raw = r.resp[r.idx].raw, last = !ad && r.idx === n - 1, pctDone = ad ? Math.min(100, r.doneResp.length / total * 100) : (r.idx / n * 100);
  return `<div class="runner-wrap ${r.large ? 'large' : ''}">${head}<div class="run-body"><div><div class="row sp small muted" style="margin-bottom:6px"><span>Question ${r.idx + 1}${ad ? '' : ' of ' + n}${q._probe ? '' : ''}</span><span>${DIFF[q.diff] ? '' : ''}${esc(q.topic)}</span></div><div class="progress" role="progressbar" aria-valuenow="${Math.round(pctDone)}" aria-valuemin="0" aria-valuemax="100" aria-label="Progress"><i style="width:${pctDone}%"></i></div></div>
    <div class="qcard">${qBody(q, raw)}${answerInput(q, raw)}</div>
    ${free ? `<div class="dots" aria-label="Question navigator">${r.items.map((it, i) => `<button class="${isBlank(r.resp[i].raw) ? '' : 'ans'} ${i === r.idx ? 'cur' : ''}" data-a="jump" data-i="${i}" aria-label="Go to question ${i + 1}">${i + 1}</button>`).join('')}</div>` : ''}
    <div class="row sp"><div class="row">${free && r.idx > 0 ? '<button class="btn ghost" data-a="prevQ">Back</button>' : ''}${a.allowSkip ? '<button class="btn ghost" data-a="skipQ">Skip</button>' : ''}${voiceBtns()}</div><button class="btn lg" data-a="nextQ">${last ? 'Finish' : 'Next'}</button></div></div></div>`;
}
function runTick() {
  const r = U.run; if (!r || r.phase !== 'q') return;
  const t = document.getElementById('tmr');
  if (r.deadline) { const left = r.deadline - Date.now(); if (t) { t.textContent = clock(left); t.classList.toggle('low', left < 60000); } if (left <= 0) { r.timeUp = true; if (r.a.autoSubmit !== false) { submitRun(true); return; } } }
  if (r.perQ) { const el = Date.now() - r.qStart + r.resp[r.idx].ms * 0, left = r.perQ - el, b = document.getElementById('qbar'); if (b) b.style.width = Math.max(0, left / r.perQ * 100) + '%'; if (left <= 0) advance({ timedOut: true }); }
}
function advance(opt = {}) {
  const r = U.run, a = r.a, ad = a.model === 'adaptive'; flushTime(); const e = r.resp[r.idx], q = curQ();
  if (opt.timedOut) e.timedOut = true;
  if (ad) {
    const resp = makeResponse(r.doneResp.length + 1, q, e.raw, e.ms, { visited: true, changed: e.changed, visits: e.visits, timedOut: e.timedOut, probe: !!q._probe });
    r.doneResp.push(resp); r.ad.record(resp);
    if (!r.preview && window.BrainovaAPI) window.BrainovaAPI.saveResponse(r.attId, resp, { asmtId: a.id, studentId: r.st.id, partial: true });
    if (r.ad.done()) { r.stop = r.ad.done(); r.phase = 'review'; submitRun(false); return; }
    const nq = r.ad.next(); r.items.push(Object.assign(present(nq, r.R, a.shuffleOpts), { _probe: !!nq._probe })); r.resp.push({ raw: null, ms: 0, visits: 1, changed: 0, timedOut: false }); r.idx++;
  } else {
    if (r.idx >= r.items.length - 1) { r.phase = 'review'; if (!a.allowBack || r.perQ) { submitRun(false); return; } }
    else r.idx++;
    if (r.phase === 'q') r.resp[r.idx].visits++;
  }
  r.qStart = Date.now(); voiceNext(); render(); scrollTop();
}
function submitRun(forced) {
  const r = U.run; if (!r || r.submitting) return; r.submitting = true; if (r.phase === 'q') flushTime();
  const a = r.a, st = r.st, now = Date.now(), ad = a.model === 'adaptive';
  const att = makeAttempt(a, st, { started: r.started, finished: now, durationMs: now - r.started, events: r.events.concat(forced ? [{ t: now, type: 'time-up', note: 'Submitted automatically when time ran out' }] : []), preview: r.preview });
  att.id = r.attId || att.id; // keep the id any in-progress adaptive answers were already saved under
  if (ad) { att.items = r.items.slice(0, r.doneResp.length).map(i => { const c = JSON.parse(JSON.stringify(i)); delete c._probe; return c; }); att.responses = r.doneResp; att.trace = r.ad.trace; att.stopReason = r.stop || r.ad.done() || 'Submitted'; }
  else { att.items = r.items; att.responses = r.items.map((q, i) => { const e = r.resp[i]; return makeResponse(i + 1, q, e.raw, e.ms, { visited: e.visits > 0, changed: e.changed, visits: e.visits, timedOut: e.timedOut }); }); }
  att.attemptNo = attemptsOf(a.id, st.id).length + 1; att.application = a.application; att.supervision = a.parentSupervision ? (supOf(a.id, st.id) ? { confirmed: true, by: supOf(a.id, st.id).by, at: supOf(a.id, st.id).at } : null) : null;
  att.released = a.resultVis === 'instant'; att.review = { notes: '', override: '', finalized: false };
  if (r.preview) { U.previewAtt = att; att.released = true; } else { S.attempts.push(att); save(); if (window.BrainovaAPI) window.BrainovaAPI.saveAttempt(att, AN(att)); }
  r.att = att; r.phase = 'done'; voiceFinish(); setTimeout(rewardFx, 120); try { if (document.fullscreenElement) document.exitFullscreen(); } catch (e) { } render(); scrollTop();
}
function doneView() {
  const r = U.run, att = r.att;
  return `<div class="runner-wrap"><div class="run-head"><b>${esc(r.a.title)}</b></div><div class="run-body"><div class="qcard" style="text-align:center;padding:36px 22px">${rewardBlock(att)}<h1 style="margin:8px 0">Submitted</h1><p class="muted">${esc(r.st.name.split(' ')[0])}, your answers were saved. You answered ${att.responses.filter(x => x.status !== 'unanswered' && x.status !== 'skipped').length} of ${att.responses.length} questions in ${fmtDur(att.durationMs)}.</p>
    ${att.released ? '<p style="margin-top:10px">Your results are ready.</p>' : '<p style="margin-top:10px">Your teacher will assess your test and give you insights on how you can cover your weak areas.</p>'}<div class="row" style="justify-content:center;margin-top:18px">${att.released ? `<button class="btn teal" data-a="finishRun" data-view="report">View results</button>` : ''}<button class="btn ghost" data-a="finishRun" data-view="home">${r.preview ? 'Back to the builder' : 'Done'}</button></div></div></div></div>`;
}

/* ===================== TEACHER-LED LIVE CLASS ===================== */
VIEWS.live = function () {
  const live = S.assessments.filter(a => a.mode === 'live' && a.status === 'published'), ls = U.liveSetup || { asmtId: live[0] && live[0].id, ids: null };
  if (!live.length) return `<div class="page-head"><div><h1>Live class</h1></div></div><div class="empty">No published live assessment yet. Create one in Assessments and choose “Teacher-led live assessment”.</div>`;
  const a = asmtById(ls.asmtId) || live[0], ids = ls.ids || a.assignedTo;
  return `<div class="page-head"><div><h1>Live class</h1><p>Show one question at a time on a shared screen. Students answer on their own devices. In this preview the class is simulated on one screen.</p></div></div>
  <div class="card"><div class="grid g2">${field('Assessment', `<select data-a="liveSel">${optList(live.map(x => ({ v: x.id, l: x.title })), a.id)}</select>`)}<div class="kpi"><b>${sumBy(DIFF_KEYS, k => a.dist[k] || 0)}</b><span>questions · ${a.perQuestionSec || 30}s each</span></div></div>
    <div class="row sp" style="margin-top:14px"><h3>Students joining (${ids.length})</h3><div class="row"><button class="btn sm ghost" data-a="liveAll">Select assigned</button><button class="btn sm ghost" data-a="liveNone">Clear</button></div></div>
    <div class="grid g3" style="margin-top:8px">${S.students.filter(s => !a.grades.length || a.grades.includes(s.grade) || ids.includes(s.id)).map(s => `<label class="chk"><input type="checkbox" data-a="liveTgl" data-id="${s.id}" ${ids.includes(s.id) ? 'checked' : ''}><span>${esc(s.name)} <span class="muted small">Grade ${s.grade == null ? '—' : s.grade}</span></span></label>`).join('')}</div>
    <div class="row" style="margin-top:16px"><button class="btn teal lg" data-a="liveStart" ${ids.length ? '' : 'disabled'}>Open lobby</button></div></div>`;
};
function liveStartSession(a, ids) {
  const seed = uid('live'), fx = buildFixed(a, S.bank, seed), R = rng(seed);
  if (!fx.items.length) { toast('No approved questions are available for this assessment.'); return; }
  const students = ids.map(studentById).filter(Boolean);
  U.live = { a, seed, items: fx.items.map(q => present(q, R, a.shuffleOpts)), students, phase: 'lobby', idx: -1, code: String(Math.floor(100000 + Math.random() * 900000)), answers: [], pending: [], sim: true, auto: !!a.autoAdvance, device: students[0] && students[0].id, qStart: 0, qMs: 0, pausedAt: 0, pausedTotal: 0, revealAt: 0, ability: {}, att: null, R };
  students.forEach(s => { const h = hashStr(s.id + seed); U.live.ability[s.id] = { theta: ((h % 200) - 90) / 100, speed: 0.4 + (h % 7) / 10 }; });
  render(); scrollTop();
}
function liveElapsed() { const L = U.live; return (L.phase === 'paused' ? L.pausedAt : Date.now()) - L.qStart - L.pausedTotal; }
function liveBegin(i) {
  const L = U.live, q = L.items[i]; L.idx = i; L.phase = 'q'; L.qStart = Date.now(); L.pausedTotal = 0; L.qMs = Math.max(10, L.a.perQuestionSec || Math.min(60, q.time || 30)) * 1000; L.answers[i] = {};
  L.pending = [];
  if (L.sim) { const R = rng(L.seed + i); L.students.forEach(s => { if (s.id === L.device) return; const ab = L.ability[s.id]; if (R.f() < 0.06) return; const at = L.qMs * (0.12 + 0.75 * R.f()) * Math.min(1, ab.speed + 0.2); L.pending.push({ sid: s.id, at, raw: simAnswer(q, ab, R) }); }); }
  render();
}
function simAnswer(q, ab, R) {
  const good = R.f() < P(ab.theta, q.b, q.c);
  if (q.type === 'mcq' || q.type === 'tf') return (good ? q.options.find(o => o.id === q.answer) : R.pick(q.options.filter(o => o.id !== q.answer))).id;
  if (q.type === 'num') return String(good ? q.answer : (q.wrong && q.wrong.length ? R.pick(q.wrong)[0] : q.answer + R.int(1, 9)));
  if (q.type === 'fill') return good ? q.accept[0] : 'not sure'; if (q.type === 'short') return good ? q.keywords[0] : 'unsure';
  if (q.type === 'order') return good ? q.items.slice() : R.shuffle(q.items); if (q.type === 'match') return q.pairs.map((p, i) => good ? p[1] : q.pairs[(i + 1) % q.pairs.length][1]);
  return 'response';
}
function liveReveal() { const L = U.live; if (L.phase !== 'q' && L.phase !== 'paused') return; L.phase = 'reveal'; L.revealAt = Date.now(); L.pending = []; if (L.auto) L.autoAt = Date.now() + 6000; render(); }
function liveEnd() {
  const L = U.live, a = L.a, ids = [];
  L.students.forEach(s => {
    const att = makeAttempt(a, s, { mode: 'live', started: L.qStart ? Date.now() - L.items.length * L.qMs : Date.now(), finished: Date.now(), durationMs: L.items.length * L.qMs });
    att.items = L.items; att.responses = L.items.map((q, i) => { const x = (L.answers[i] || {})[s.id]; return makeResponse(i + 1, q, x ? x.raw : null, x ? x.ms : 0, { visited: false }); });
    att.supervision = null; att.review = { notes: '', override: '', finalized: false }; att.released = false; att.attemptNo = attemptsOf(a.id, s.id).length + 1; S.attempts.push(att); ids.push(att.id);
    if (window.BrainovaAPI) window.BrainovaAPI.saveAttempt(att, AN(att));
  });
  save(); U.live = null; U.resTab = 'class'; U.params = { asmt: a.id }; U.view = 'results'; U.role = U.role === 'admin' ? 'admin' : 'teacher'; render(); toast('Live session saved. Class report is ready.'); scrollTop();
}
function liveView() {
  const L = U.live, a = L.a, q = L.items[L.idx], n = L.items.length;
  const answered = L.idx >= 0 ? Object.keys(L.answers[L.idx] || {}).length : 0;
  if (L.phase === 'lobby') return `<div class="stage"><div class="bar"><b style="font-family:var(--head);font-size:18px">${esc(a.title)}</b><div class="row"><button class="btn ghost" data-a="liveExit">Cancel session</button></div></div>
    <div class="stage-main"><div class="stage-q"><div class="eyebrow" style="color:#9FB0CF">Join code</div><div class="big" style="font-size:clamp(48px,9vw,110px);letter-spacing:.12em">${L.code}</div><p style="font-size:20px;color:#C9D3E8">${L.students.length} students are in the room. ${n} questions · ${Math.round(L.qMs / 1000) || (a.perQuestionSec || 30)} seconds each.</p>
      <div class="row"><button class="btn teal lg" data-a="liveNext">Start the first question</button></div><p style="color:#9FB0CF;max-width:60ch">Preview note: classmates are simulated here. In production each student joins with this code on their own device.</p></div>
      <aside><h3 style="color:#fff">Students</h3><div class="roster">${L.students.map(s => `<div><span>${esc(s.name)}</span><span>joined</span></div>`).join('')}</div></aside></div></div>`;
  if (L.phase === 'ended') return '';
  const reveal = L.phase === 'reveal', counts = {};
  if (q.type === 'mcq' || q.type === 'tf') Object.values(L.answers[L.idx] || {}).forEach(x => counts[x.raw] = (counts[x.raw] || 0) + 1);
  const left = L.qMs - liveElapsed(), dev = L.students.find(s => s.id === L.device), devAns = dev && (L.answers[L.idx] || {})[dev.id];
  const optTiles = (q.type === 'mcq' || q.type === 'tf') ? `<div class="stage-opts">${q.options.map((o, i) => `<div class="stage-opt ${reveal ? (o.id === q.answer ? 'right' : 'wrong') : ''}"><span class="ltr">${q.type === 'tf' ? o.t[0] : 'ABCD'[i]}</span><span>${esc(o.t)}</span>${reveal ? `<span class="cnt">${counts[o.id] || 0}</span><span class="distb" style="width:${answered ? (counts[o.id] || 0) / answered * 100 : 0}%"></span>` : ''}</div>`).join('')}</div>` : `<p style="font-size:22px;color:#C9D3E8">Students type or arrange their answer on their device.${reveal ? `<br><b style="color:#5EEAD4">Answer: ${esc(correctText(q))}</b>` : ''}</p>`;
  return `<div class="stage"><div class="bar"><div><b style="font-family:var(--head);font-size:18px">${esc(a.title)}</b><span style="color:#9FB0CF;margin-left:12px">Code ${L.code}</span></div><div class="row"><span class="pill" style="background:#1E2D4A;color:#fff;border-color:#2B3A58">Question ${L.idx + 1} of ${n}</span>
      <label class="chk" style="color:#fff"><input type="checkbox" data-a="liveAuto" ${L.auto ? 'checked' : ''}><span>Auto-advance</span></label><label class="chk" style="color:#fff"><input type="checkbox" data-a="liveSim" ${L.sim ? 'checked' : ''}><span>Simulate class</span></label>
      ${L.phase === 'q' ? '<button class="btn ghost" data-a="livePause">Pause</button>' : L.phase === 'paused' ? '<button class="btn teal" data-a="livePause">Resume</button>' : ''}${L.phase === 'q' || L.phase === 'paused' ? '<button class="btn ghost" data-a="liveReveal">Show answer</button>' : ''}
      <button class="btn teal" data-a="liveNext">${L.idx >= n - 1 && reveal ? 'End and see results' : reveal ? 'Next question' : 'Skip to next'}</button><button class="btn ghost" data-a="liveEndNow">End session</button></div></div>
    <div class="stage-main"><div class="stage-q"><div class="row sp"><span class="eyebrow" style="color:#9FB0CF">${esc(q.topic)} · ${DIFF[q.diff].name}</span><div class="big-timer ${left < 6000 ? 'low' : ''}" id="ltmr">${reveal ? '' : (L.phase === 'paused' ? 'Paused' : clock(left))}</div></div>
      ${q.passage ? `<div class="passage" style="background:#17233B;color:#E8ECF6">${esc(q.passage)}</div>` : ''}<div class="big">${esc(q.text)}</div>${q.code ? `<pre class="code">${esc(q.code)}</pre>` : ''}${q.image ? `<img class="qimg" style="max-height:300px" src="${esc(q.image)}" alt="Diagram">` : ''}${optTiles}
      ${reveal && q.explanation ? `<div class="banner" style="background:#17233B;color:#E8ECF6;border-color:#2B3A58;font-size:18px">${esc(q.explanation)}</div>` : ''}</div>
      <aside><div><h3 style="color:#fff">${answered} of ${L.students.length} answered</h3><div class="progress" style="background:#2B3A58"><i style="width:${answered / L.students.length * 100}%"></i></div></div>
        <div class="roster" id="roster">${L.students.map(s => { const x = (L.answers[L.idx] || {})[s.id]; return `<div class="${x ? 'done' : ''}"><span>${esc(s.name)}</span><span>${x ? (reveal && (q.type === 'mcq' || q.type === 'tf') ? (x.raw === q.answer ? '✓' : '✗') : (reveal ? (gradeAnswer(q, x.raw).status === 'correct' ? '✓' : '✗') : 'answered')) : (reveal ? 'no answer' : '…')}</span></div>`; }).join('')}</div>
        <div class="phone"><div class="row sp"><b class="small">Student device</b><select data-a="liveDev" style="font-size:12px;padding:4px" aria-label="Student on this device">${L.students.map(s => `<option value="${s.id}" ${s.id === L.device ? 'selected' : ''}>${esc(s.name)}</option>`).join('')}</select></div>
          ${devAns ? `<div class="banner ok small">Answer sent. Waiting for the class.</div>` : (L.phase === 'q' ? `<div class="small" style="font-weight:600">${esc(q.text.slice(0, 80))}${q.text.length > 80 ? '…' : ''}</div>${(q.type === 'mcq' || q.type === 'tf') ? `<div class="opts" style="margin-top:4px">${q.options.map((o, i) => `<button class="opt" data-a="liveAns" data-v="${o.id}"><span class="ltr">${q.type === 'tf' ? o.t[0] : 'ABCD'[i]}</span><span>${esc(o.t)}</span></button>`).join('')}</div>` : `<input type="text" id="liveTxt" placeholder="Type answer" aria-label="Answer"><button class="btn sm" data-a="liveTxtSend">Send</button>`}` : `<div class="small muted">${L.phase === 'paused' ? 'Paused by the teacher.' : 'Answering is closed.'}</div>`)}</div></aside></div></div>`;
}
function liveTick() {
  const L = U.live; if (!L || L.phase === 'lobby' || L.phase === 'ended') return;
  if (L.phase === 'q') {
    const el = liveElapsed(); let changed = false;
    L.pending = L.pending.filter(p => { if (p.at <= el) { L.answers[L.idx][p.sid] = { raw: p.raw, ms: Math.round(p.at) }; changed = true; return false; } return true; });
    const left = L.qMs - el, t = document.getElementById('ltmr'); if (t) { t.textContent = clock(left); t.classList.toggle('low', left < 6000); }
    if (Object.keys(L.answers[L.idx]).length >= L.students.length && L.qMs - el > 1500 && !L.allIn) { L.allIn = true; liveReveal(); return; }
    if (left <= 0) { L.allIn = false; liveReveal(); if (L.auto) L.autoAt = Date.now() + 6000; return; }
    if (changed) { const f = document.activeElement; if (!(f && (f.tagName === 'INPUT' || f.tagName === 'SELECT'))) render(); }
  } else if (L.phase === 'reveal' && L.auto && L.autoAt && Date.now() > L.autoAt) { L.autoAt = 0; liveNextQ(); }
}
function liveNextQ() { const L = U.live; L.allIn = false; L.autoAt = 0; if (L.idx >= L.items.length - 1) { liveEnd(); return; } liveBegin(L.idx + 1); }
setInterval(() => { if (U.run) runTick(); if (U.live) liveTick(); }, 250);
