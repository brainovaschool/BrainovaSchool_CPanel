/* ===================== LEARNING PATHWAYS =====================
   Teacher/admin-only workflow that turns a graded attempt's weak,
   borderline and strong topics into three course-recommendation
   options (Short, Medium, Long). Lives on the attempt itself
   (att.pathways), moves draft -> final -> sent -> shared, and is
   never shown to the student it is about. A finalised plan can also
   be forwarded to the teacher (assignedTeacherAt) independently of
   being shared with the parent - the two are separate flags, not
   alternatives, so an admin can do either, both, or neither. */
const PW_STATUS_NAME = { draft: 'Draft', final: 'Finalised', sent: 'Sent to administrator', shared: 'Shared with parent' };
const pwFileStem = att => att.studentName.replace(/\s+/g, '-').toLowerCase() + '-' + att.id.slice(-4);

function pwReady(att) { return AN(att).overallIdx != null; }
function pwShared(att) { return !!(att && att.pathways && att.pathways.status === 'shared'); }

// Recommended weeks for a topic at a given estimated level - more time
// for a weaker topic, a light touch for one already secure or
// borderline. Only ever the STARTING point: a teacher can override any
// step's own weeks (see rebalanceWeeks), or the whole plan's total,
// once a pathway is generated.
function weeksForLevel(idx) {
  if (idx == null || idx <= 0) return 3; // Foundation, or not yet assessed
  if (idx === 1) return 2; // Developing
  return 1; // Borderline/Intermediate and above
}
function sumWeeks(steps) { return steps.reduce((s, x) => s + (typeof x === 'string' ? 0 : (+x.weeks || 0)), 0); }
// A step is either a plain string (a pathway saved before per-step
// weeks existed - sample data, or any real plan finalised before this
// change) or an object {topic, level, weeks, locked, note}. Every
// reader of .steps (the panel, the embedded report section, the PDF,
// the CSV export) goes through these two so an older plan keeps
// rendering exactly as it always did, with no weeks shown or editable.
function pwStepLine(s) { return typeof s === 'string' ? s : `${s.topic}${s.level && s.level !== '—' ? ' (' + s.level + ')' : ''}${s.note ? ': ' + s.note : ''}`; }
function pwStepWeeks(s) { return typeof s === 'string' ? '' : (s.weeks || ''); }

// Scales every UNLOCKED step's weeks to fit newTotal (locked ones - a
// teacher's own hand-edit - keep their exact number), using the
// largest-remainder method so whole weeks always sum to exactly
// newTotal, with a floor of 1 week per step. Falls back to scaling
// every step if all of them are locked, so the total is still honoured
// even then. Mutates steps in place.
function rebalanceWeeks(steps, newTotal) {
  const total = Math.max(1, Math.round(+newTotal) || 0);
  let unlocked = steps.filter(s => !s.locked);
  if (!unlocked.length) unlocked = steps;
  const lockedSum = steps.reduce((s, x) => s + (unlocked.includes(x) ? 0 : (+x.weeks || 0)), 0);
  const pool = Math.max(unlocked.length, total - lockedSum);
  const weightSum = unlocked.reduce((s, x) => s + Math.max(1, +x.weeks || 1), 0);
  const raw = unlocked.map(s => Math.max(1, +s.weeks || 1) / weightSum * pool);
  const floors = raw.map(x => Math.max(1, Math.floor(x)));
  let left = pool - floors.reduce((a, b) => a + b, 0);
  const order = raw.map((x, i) => ({ i, frac: x - Math.floor(x) })).sort((a, b) => b.frac - a.frac);
  for (let k = 0; k < order.length && left > 0; k++) { floors[order[k].i]++; left--; }
  unlocked.forEach((s, i) => { s.weeks = floors[i]; });
}

// Builds steps from the exact same weak/ready classification
// placement() (engine.js) already computes for the Diagnostic Report's
// own "Suggested learning plan"/"Next steps" cards - same topics, same
// wording source - so the Pathway panel and the Report never describe
// the same topic two different ways.
function pwBuildOptions(att) {
  const an = AN(att), P = an.placement, tp = an.topics.filter(t => !t.probe && t.idx != null);
  const get = n => an.topics.find(t => t.topic === n);
  const mkStep = (t, note) => ({ topic: t.topic, level: lvName(t.idx), weeks: weeksForLevel(t.idx), locked: false, note });

  const weak = tp.filter(t => t.idx <= 1).sort((a, b) => a.idx - b.idx)
    .map(t => mkStep(t, t.improve.length ? 'Practise: ' + t.improve.slice(0, 2).join('; ') : 'Needs more practice at this level'));
  const ready = tp.filter(t => { const pre = PREREQ[t.topic], pt = pre && get(pre); return t.idx >= 3 || (t.idx === 2 && (!pt || pt.idx == null || pt.idx >= 1)); })
    .sort((a, b) => b.idx - a.idx)
    .map(t => mkStep(t, t.idx >= 3 ? 'Secure; ready for extension or the next unit' : 'Ready to learn the next steps'));
  const readyTopics = new Set(ready.map(r => r.topic));
  const mid = tp.filter(t => t.idx === 2 && !readyTopics.has(t.topic)).map(t => mkStep(t, 'Borderline - a little more practice before moving on'));
  const asExtension = s => Object.assign({}, s, { note: 'Extension: ' + s.note });

  const short = { label: 'Short', headline: weak.length ? `Quick revision of ${weak.slice(0, 2).map(t => t.topic).join(' and ')}` : 'Short consolidation at the current level',
    steps: weak.length ? weak.slice(0, 2) : mid.length ? mid.slice(0, 2) : ready.slice(0, 1) };
  if (!short.steps.length) short.steps = [{ topic: 'Mixed practice', level: '—', weeks: 2, locked: false, note: 'Continue at the current level with mixed practice.' }];

  const medium = { label: 'Medium', headline: 'A balanced plan covering weak and borderline topics, with one extension topic',
    steps: weak.concat(mid).concat(ready.length ? [asExtension(ready[0])] : []) };
  if (!medium.steps.length) medium.steps = short.steps.slice();

  const long = { label: 'Long', headline: 'A full-term plan: close every gap, then move into extension work',
    steps: weak.concat(mid).concat(ready.map(asExtension)) };
  if (!long.steps.length) long.steps = medium.steps.slice();

  if (P.missing && P.missing.length) {
    const toStep = m => ({ topic: 'Prerequisite check', level: '—', weeks: 1, locked: false, note: m });
    medium.steps = [toStep(P.missing[0])].concat(medium.steps);
    long.steps = P.missing.map(toStep).concat(long.steps);
  }

  [short, medium, long].forEach(o => { o.weeks = sumWeeks(o.steps); });
  return { short, medium, long };
}

function pwJson(att) {
  const pw = att.pathways; if (!pw) return null;
  return { attempt_id: att.id, student_external_id: att.studentId, student_name: att.studentName, grade: att.grade, status: pw.status, selected: pw.selected || null, notes: pw.notes || '',
    assigned_teacher_at: pw.assignedTeacherAt ? new Date(pw.assignedTeacherAt).toISOString() : null,
    updated_at: new Date(pw.updatedAt || Date.now()).toISOString(),
    options: ['short', 'medium', 'long'].map(k => ({ tier: k, label: pw.options[k].label, weeks: pw.options[k].weeks, headline: pw.options[k].headline, steps: pw.options[k].steps })) };
}
function pwExportable() { return S.attempts.filter(a => !a.preview && a.pathways && a.pathways.status !== 'draft'); }

const EXPORTS_PATHWAYS_HEAD = ['attempt_id', 'student_id', 'student_name', 'grade', 'assessment', 'status', 'selected_tier', 'weeks', 'headline', 'notes', 'updated'];
Object.assign(EXPORTS, {
  pathways: () => toCSV([EXPORTS_PATHWAYS_HEAD].concat(pwExportable().map(a => { const pw = a.pathways, o = pw.selected && pw.options[pw.selected];
    return [a.id, a.studentId, a.studentName, a.grade, a.title, pw.status, pw.selected || '', o ? o.weeks : '', o ? o.headline : '', pw.notes || '', new Date(pw.updatedAt || a.finished).toISOString()]; }))),
  pathwayplan: () => toCSV([['attempt_id', 'student_id', 'student_name', 'tier', 'selected', 'step_no', 'weeks', 'step']].concat(pwExportable().flatMap(a => { const pw = a.pathways;
    return ['short', 'medium', 'long'].flatMap(k => pw.options[k].steps.map((s, i) => [a.id, a.studentId, a.studentName, k, pw.selected === k ? 'yes' : 'no', i + 1, pwStepWeeks(s), pwStepLine(s)]));
  })))
});

/* ---------- sample data (mirrors addSampleCheck / addStretchSample) ----------
   Deliberately left in the OLD plain-string-step shape (not rebuilt
   through pwBuildOptions) so these two attempts double as a permanent,
   easy-to-find check that the legacy-format fallback still works
   everywhere, not just freshly-generated plans. */
function addSamplePathways(D) {
  const check = D.attempts.find(a => a.asmtId === 'asm_g5check');
  if (check) check.pathways = { options: pwBuildOptions(check), selected: 'medium', notes: 'Shared with Fatima’s parent after the Grade 5 check.', status: 'shared', updatedAt: Date.now() - 86400000 };
  const stretch = D.attempts.find(a => a.asmtId === 'asm_g5stretch');
  if (stretch) stretch.pathways = { options: pwBuildOptions(stretch), selected: 'long', notes: '', status: 'final', updatedAt: Date.now() - 3600000 };
  D.samplePathways = true;
}

/* ---------- dashboard widget ---------- */
function pwInbox() {
  if (U.role !== 'admin' && U.role !== 'teacher') return '';
  const eligible = S.attempts.filter(a => !a.preview && pwReady(a));
  if (!eligible.length) return '';
  const needs = eligible.filter(a => !a.pathways || a.pathways.status === 'draft').length;
  const sent = eligible.filter(a => a.pathways && a.pathways.status === 'sent').length;
  const shared = eligible.filter(a => pwShared(a)).length;
  return `<div class="card"><div class="row sp"><h2>Learning pathways</h2><button class="btn sm ghost" data-a="go" data-v="pathways">Open</button></div>
    <div class="tiles"><div class="tile"><b>${needs}</b><span>Need a pathway</span></div>${U.role === 'admin' ? `<div class="tile"><b>${sent}</b><span>Waiting for your review</span></div>` : ''}<div class="tile"><b>${shared}</b><span>Shared with parents</span></div></div></div>`;
}

/* ---------- main inbox page ---------- */
VIEWS.pathways = function () {
  const eligible = S.attempts.filter(a => !a.preview && pwReady(a)).sort((a, b) => b.finished - a.finished);
  const row = a => { const pw = a.pathways; return `<div class="row sp sub"><div><b>${esc(a.studentName)}</b><div class="muted small">${esc(a.title)} · ${fmtDate(a.finished)}</div></div>
    <div class="row">${pw ? `<span class="pill ${pw.status === 'shared' ? 'ok' : pw.status === 'sent' ? 'warn' : ''}">${esc(PW_STATUS_NAME[pw.status])}</span>` : '<span class="muted small">Not generated</span>'}${pw && pw.assignedTeacherAt ? '<span class="pill ok">Forwarded to teacher</span>' : ''}<button class="btn sm" data-a="openAtt" data-id="${a.id}">Open report</button></div></div>`; };
  if (!eligible.length) return `<div class="page-head"><div><h1>Learning pathways</h1></div></div><div class="empty">No graded attempts are ready for a learning pathway yet.</div>`;
  const assigned = eligible.filter(a => a.pathways && a.pathways.assignedTeacherAt);
  const needs = eligible.filter(a => !a.pathways || a.pathways.status === 'draft');
  const sent = eligible.filter(a => a.pathways && a.pathways.status === 'sent');
  const final = eligible.filter(a => a.pathways && a.pathways.status === 'final');
  const shared = eligible.filter(a => pwShared(a));
  return `<div class="page-head"><div><h1>Learning pathways</h1><p>Short, Medium and Long course recommendations built from each student's results. A pathway stays a draft until finalised, and reaches a parent only once the administrator shares it, or a teacher once it's forwarded to them.</p></div></div>
  ${U.role === 'teacher' && assigned.length ? `<div class="card"><h2>Assigned to you <span class="pill ok">${assigned.length}</span></h2><div class="stack">${assigned.map(row).join('')}</div></div>` : ''}
  <div class="card"><h2>Needs attention <span class="pill warn">${needs.length}</span></h2>${needs.length ? `<div class="stack">${needs.map(row).join('')}</div>` : '<p class="muted">Nothing waiting.</p>'}</div>
  ${U.role === 'admin' ? `<div class="card"><h2>Sent for review <span class="pill warn">${sent.length}</span></h2>${sent.length ? `<div class="stack">${sent.map(row).join('')}</div>` : '<p class="muted">Nothing waiting for review.</p>'}</div>` : ''}
  <div class="card"><h2>Finalised <span class="pill ok">${final.length}</span></h2>${final.length ? `<div class="stack">${final.map(row).join('')}</div>` : '<p class="muted">None yet.</p>'}</div>
  <div class="card"><h2>Shared with parents <span class="pill ok">${shared.length}</span></h2>${shared.length ? `<div class="stack">${shared.map(row).join('')}</div>` : '<p class="muted">None yet.</p>'}</div>`;
};

/* ---------- staff editing panel (shown above a single report) ---------- */
// One step row: a legacy (string) step renders exactly as it always
// did; a real step shows its topic/level/note plus an editable weeks
// field while the plan is still a draft (locks that one step so a
// later total-weeks change leaves it alone), or just a plain "Nw" pill
// once the plan is finalised.
function pwStepRow(att, k, i, s, editable) {
  if (typeof s === 'string') return `<li>${esc(s)}</li>`;
  const weekBit = editable
    ? `<span class="row" style="gap:4px"><input type="number" min="1" style="width:52px" data-m="pwWeek.${att.id}.${k}.${i}" data-t="num" data-r value="${s.weeks}">w${s.locked ? `<button type="button" class="btn sm ghost" data-a="pwUnlock" data-id="${att.id}" data-k="${k}" data-i="${i}" title="Let this step follow the plan's total again">Unlock</button>` : ''}</span>`
    : `<span class="pill">${s.weeks}w</span>`;
  return `<li><div class="row sp" style="gap:8px;align-items:flex-start"><span><b>${esc(s.topic)}</b>${s.level && s.level !== '—' ? ` <span class="muted small">(${esc(s.level)})</span>` : ''}${s.note ? `<div class="muted small">${esc(s.note)}</div>` : ''}</span>${weekBit}</div></li>`;
}
function pwPanel(att) {
  if (!pwReady(att)) return `<div class="card noprint"><h2>Learning pathway</h2><p class="muted">Not enough graded answers yet to build a learning pathway for this attempt.</p></div>`;
  const pw = att.pathways, isAdmin = U.role === 'admin';
  if (!pw) return `<div class="card noprint"><h2>Learning pathway</h2><p class="muted small" style="margin-bottom:10px">Generate Short, Medium and Long course recommendations from this student’s results.</p><button class="btn teal" data-a="pwGen" data-id="${att.id}">Generate pathway options</button></div>`;
  const editable = pw.status === 'draft', opts = ['short', 'medium', 'long'];
  return `<div class="card noprint"><div class="row sp"><h2>Learning pathway</h2><span class="row" style="gap:6px"><span class="pill ${pw.status === 'shared' ? 'ok' : pw.status === 'sent' ? 'warn' : pw.status === 'final' ? 'ok' : ''}">${esc(PW_STATUS_NAME[pw.status])}</span>${pw.assignedTeacherAt ? '<span class="pill ok">Forwarded to teacher</span>' : ''}</span></div>
    <div class="grid g3" style="margin-top:8px">${opts.map(k => { const o = pw.options[k], legacy = o.steps.some(s => typeof s === 'string');
      return `<label class="sub stack"><div class="row sp"><b>${esc(o.label)}</b>${editable && !legacy ? `<span class="row" style="gap:4px"><span class="muted small">Target</span><input type="number" min="${o.steps.length}" style="width:52px" data-m="pwTotal.${att.id}.${k}" data-t="num" data-r value="${o.weeks}">w</span>` : `<span class="muted small">~${o.weeks}w</span>`}</div>
      ${editable ? `<label class="chk"><input type="radio" name="pwSel" data-a="pwSelect" data-id="${att.id}" data-k="${k}" ${pw.selected === k ? 'checked' : ''}><span>Use this option</span></label>` : (pw.selected === k ? '<span class="pill ok">Selected</span>' : '')}
      <p class="small">${esc(o.headline)}</p><ol class="ul small">${o.steps.map((s, i) => pwStepRow(att, k, i, s, editable && !legacy)).join('')}</ol></label>`; }).join('')}</div>
    ${editable ? field('Notes for the administrator or parent (optional)', `<textarea data-a="pwNote" data-id="${att.id}">${esc(pw.notes || '')}</textarea>`) : (pw.notes ? `<p class="small muted" style="margin-top:10px"><b>Note:</b> ${esc(pw.notes)}</p>` : '')}
    <div class="row" style="margin-top:12px;flex-wrap:wrap">
      ${editable ? `<button class="btn ghost" data-a="pwGen" data-id="${att.id}">Regenerate options</button><button class="btn teal" data-a="pwFinalize" data-id="${att.id}" ${pw.selected ? '' : 'disabled'}>Finalise</button>` : ''}
      ${pw.status === 'final' ? `<button class="btn teal" data-a="pwSend" data-id="${att.id}">Send to administrator</button>` : ''}
      ${pw.status === 'sent' && isAdmin ? `<button class="btn teal" data-a="pwShare" data-id="${att.id}">Share with parent</button>` : ''}
      ${pw.status === 'sent' && isAdmin && !pw.assignedTeacherAt ? `<button class="btn ghost" data-a="pwAssignTeacher" data-id="${att.id}">Forward to teacher</button>` : ''}
      ${pw.status !== 'draft' && isAdmin ? `<button class="btn ghost" data-a="pwReopen" data-id="${att.id}">Reopen for edits</button>` : ''}
      ${pw.status !== 'draft' ? `<button class="btn ghost" data-a="pwPdf" data-id="${att.id}">Download PDF</button>` : ''}
    </div></div>`;
}

/* ---------- embedded section inside the printable/shared report ---------- */
function pwSection(att, aud, opts = {}) {
  const pw = att.pathways;
  if (aud === 'student' || !pw || pw.status === 'draft') return '';
  if (aud === 'parent' && pw.status !== 'shared') return '';
  const sel = pw.selected, opt = sel && pw.options[sel];
  const stepLine = s => typeof s === 'string' ? s : `${pwStepLine(s)} — ${s.weeks} week${s.weeks === 1 ? '' : 's'}`;
  return `<div class="card"><h2>Learning pathway</h2><p class="muted small" style="margin-bottom:10px">${pw.status === 'shared' ? 'Shared with the parent.' : pw.status === 'sent' ? 'Sent to the administrator for review.' : 'Finalised by the teacher.'}</p>
    ${opt ? `<div class="rec"><div class="eyebrow">${esc(opt.label)} pathway · about ${opt.weeks} weeks</div><b>${esc(opt.headline)}</b><ol class="ul" style="margin-top:8px">${opt.steps.map(s => `<li>${esc(stepLine(s))}</li>`).join('')}</ol></div>`
      : `<div class="grid g3">${['short', 'medium', 'long'].map(k => `<div class="sub"><b>${esc(pw.options[k].label)}</b> <span class="muted small">(${pw.options[k].weeks}w)</span><p class="small" style="margin-top:4px">${esc(pw.options[k].headline)}</p></div>`).join('')}</div>`}
    ${pw.notes ? `<p class="small muted" style="margin-top:10px">${esc(pw.notes)}</p>` : ''}</div>`;
}

/* ---------- PDF (parents only ever get this once shared) ---------- */
async function downloadPathwayPdf(att) {
  if (!window.jspdf || !window.jspdf.jsPDF) { toast('PDF support did not load. Try again in a moment.'); return; }
  const pw = att.pathways; if (!pw) { toast('No pathway to download yet.'); return; }
  const opt = pw.selected && pw.options[pw.selected];
  const doc = new window.jspdf.jsPDF();
  let y = 20;
  doc.setFontSize(16); doc.text('Brainova learning pathway', 14, y); y += 10;
  doc.setFontSize(11); doc.text(`${att.studentName} - Grade ${att.grade} - ${att.title}`, 14, y); y += 7;
  doc.text(`Finished ${fmtDate(att.finished)}`, 14, y); y += 10;
  if (opt) {
    doc.setFontSize(13); doc.text(`${opt.label} pathway (about ${opt.weeks} weeks)`, 14, y); y += 8;
    doc.setFontSize(11);
    const head = doc.splitTextToSize(opt.headline, 180); doc.text(head, 14, y); y += head.length * 6 + 4;
    const legacy = opt.steps.some(s => typeof s === 'string');
    if (legacy) {
      opt.steps.forEach((s, i) => { const lines = doc.splitTextToSize(`${i + 1}. ${s}`, 180); if (y > 270) { doc.addPage(); y = 20; } doc.text(lines, 14, y); y += lines.length * 6 + 2; });
    } else {
      doc.setFontSize(10); doc.text('Week', 14, y); doc.text('Topic', 36, y); doc.text('Level', 118, y); doc.text('Notes', 148, y); y += 2;
      doc.line(14, y, 196, y); y += 6; doc.setFontSize(10);
      let weekCursor = 1;
      opt.steps.forEach(s => {
        if (y > 265) { doc.addPage(); y = 20; }
        const weekLabel = s.weeks > 1 ? `${weekCursor}-${weekCursor + s.weeks - 1}` : `${weekCursor}`;
        const topicLines = doc.splitTextToSize(s.topic, 78), noteLines = doc.splitTextToSize(s.note || '', 44);
        doc.text(weekLabel, 14, y); doc.text(topicLines, 36, y); doc.text(s.level || '—', 118, y); doc.text(noteLines, 148, y);
        y += Math.max(topicLines.length, noteLines.length, 1) * 5 + 4;
        weekCursor += s.weeks;
      });
    }
  } else { doc.text('No pathway option has been selected yet.', 14, y); y += 8; }
  if (pw.notes) { if (y > 260) { doc.addPage(); y = 20; } y += 4; doc.setFontSize(11); doc.text(doc.splitTextToSize('Note: ' + pw.notes, 180), 14, y); }
  doc.save(`brainova-pathway-${pwFileStem(att)}.pdf`);
}
