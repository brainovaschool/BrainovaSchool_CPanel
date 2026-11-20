/* ===================== LEARNING PATHWAYS =====================
   Teacher/admin-only workflow that turns a graded attempt's weak,
   borderline and strong topics into three course-recommendation
   options (Short, Medium, Long). Lives on the attempt itself
   (att.pathways), moves draft -> final -> sent -> shared, and is
   never shown to the student it is about. */
const PW_STATUS_NAME = { draft: 'Draft', final: 'Finalised', sent: 'Sent to administrator', shared: 'Shared with parent' };
const pwFileStem = att => att.studentName.replace(/\s+/g, '-').toLowerCase() + '-' + att.id.slice(-4);

function pwReady(att) { return AN(att).overallIdx != null; }
function pwShared(att) { return !!(att && att.pathways && att.pathways.status === 'shared'); }

function pwBuildOptions(att) {
  const an = AN(att), P = an.placement, tp = an.topics.filter(t => !t.probe && t.idx != null);
  const weak = tp.filter(t => t.idx <= 1).sort((a, b) => a.idx - b.idx);
  const mid = tp.filter(t => t.idx === 2);
  const ready = tp.filter(t => t.idx >= 3).sort((a, b) => b.idx - a.idx);
  const line = t => `${t.topic} (${lvName(t.idx)})${t.improve.length ? ': focus on ' + t.improve[0] : ''}`;
  const extLine = t => `Extension: ${line(t)}`;

  const shortSteps = (weak.length ? weak.slice(0, 2) : mid.length ? mid.slice(0, 2) : ready.slice(0, 1)).map(line);
  const short = { label: 'Short', weeks: 2, headline: weak.length ? `Quick revision of ${weak.slice(0, 2).map(t => t.topic).join(' and ')}` : 'Short consolidation at the current level',
    steps: shortSteps.length ? shortSteps : ['Continue at the current level with mixed practice.'] };

  const mediumSteps = weak.concat(mid).map(line).concat(ready.length ? [extLine(ready[0])] : []);
  const medium = { label: 'Medium', weeks: 6, headline: 'A balanced plan covering weak and borderline topics, with one extension topic',
    steps: mediumSteps.length ? mediumSteps : short.steps.slice() };

  const longSteps = weak.concat(mid).map(line).concat(ready.map(extLine));
  const long = { label: 'Long', weeks: 12, headline: 'A full-term plan: close every gap, then move into extension work',
    steps: longSteps.length ? longSteps : medium.steps.slice() };

  if (P.missing && P.missing.length) { medium.steps = P.missing.slice(0, 1).concat(medium.steps); long.steps = P.missing.concat(long.steps); }
  return { short, medium, long };
}

function pwJson(att) {
  const pw = att.pathways; if (!pw) return null;
  return { attempt_id: att.id, student_external_id: att.studentId, student_name: att.studentName, grade: att.grade, status: pw.status, selected: pw.selected || null, notes: pw.notes || '',
    updated_at: new Date(pw.updatedAt || Date.now()).toISOString(),
    options: ['short', 'medium', 'long'].map(k => ({ tier: k, label: pw.options[k].label, weeks: pw.options[k].weeks, headline: pw.options[k].headline, steps: pw.options[k].steps })) };
}
function pwExportable() { return S.attempts.filter(a => !a.preview && a.pathways && a.pathways.status !== 'draft'); }

const EXPORTS_PATHWAYS_HEAD = ['attempt_id', 'student_id', 'student_name', 'grade', 'assessment', 'status', 'selected_tier', 'weeks', 'headline', 'notes', 'updated'];
Object.assign(EXPORTS, {
  pathways: () => toCSV([EXPORTS_PATHWAYS_HEAD].concat(pwExportable().map(a => { const pw = a.pathways, o = pw.selected && pw.options[pw.selected];
    return [a.id, a.studentId, a.studentName, a.grade, a.title, pw.status, pw.selected || '', o ? o.weeks : '', o ? o.headline : '', pw.notes || '', new Date(pw.updatedAt || a.finished).toISOString()]; }))),
  pathwayplan: () => toCSV([['attempt_id', 'student_id', 'student_name', 'tier', 'selected', 'step_no', 'step']].concat(pwExportable().flatMap(a => { const pw = a.pathways;
    return ['short', 'medium', 'long'].flatMap(k => pw.options[k].steps.map((s, i) => [a.id, a.studentId, a.studentName, k, pw.selected === k ? 'yes' : 'no', i + 1, s]));
  })))
});

/* ---------- sample data (mirrors addSampleCheck / addStretchSample) ---------- */
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
    <div class="row">${pw ? `<span class="pill ${pw.status === 'shared' ? 'ok' : pw.status === 'sent' ? 'warn' : ''}">${esc(PW_STATUS_NAME[pw.status])}</span>` : '<span class="muted small">Not generated</span>'}<button class="btn sm" data-a="openAtt" data-id="${a.id}">Open report</button></div></div>`; };
  if (!eligible.length) return `<div class="page-head"><div><h1>Learning pathways</h1></div></div><div class="empty">No graded attempts are ready for a learning pathway yet.</div>`;
  const needs = eligible.filter(a => !a.pathways || a.pathways.status === 'draft');
  const sent = eligible.filter(a => a.pathways && a.pathways.status === 'sent');
  const final = eligible.filter(a => a.pathways && a.pathways.status === 'final');
  const shared = eligible.filter(a => pwShared(a));
  return `<div class="page-head"><div><h1>Learning pathways</h1><p>Short, Medium and Long course recommendations built from each student's results. A pathway stays a draft until finalised, and reaches a parent only once the administrator shares it.</p></div></div>
  <div class="card"><h2>Needs attention <span class="pill warn">${needs.length}</span></h2>${needs.length ? `<div class="stack">${needs.map(row).join('')}</div>` : '<p class="muted">Nothing waiting.</p>'}</div>
  ${U.role === 'admin' ? `<div class="card"><h2>Sent for review <span class="pill warn">${sent.length}</span></h2>${sent.length ? `<div class="stack">${sent.map(row).join('')}</div>` : '<p class="muted">Nothing waiting for review.</p>'}</div>` : ''}
  <div class="card"><h2>Finalised <span class="pill ok">${final.length}</span></h2>${final.length ? `<div class="stack">${final.map(row).join('')}</div>` : '<p class="muted">None yet.</p>'}</div>
  <div class="card"><h2>Shared with parents <span class="pill ok">${shared.length}</span></h2>${shared.length ? `<div class="stack">${shared.map(row).join('')}</div>` : '<p class="muted">None yet.</p>'}</div>`;
};

/* ---------- staff editing panel (shown above a single report) ---------- */
function pwPanel(att) {
  if (!pwReady(att)) return `<div class="card noprint"><h2>Learning pathway</h2><p class="muted">Not enough graded answers yet to build a learning pathway for this attempt.</p></div>`;
  const pw = att.pathways, isAdmin = U.role === 'admin';
  if (!pw) return `<div class="card noprint"><h2>Learning pathway</h2><p class="muted small" style="margin-bottom:10px">Generate Short, Medium and Long course recommendations from this student’s results.</p><button class="btn teal" data-a="pwGen" data-id="${att.id}">Generate pathway options</button></div>`;
  const editable = pw.status === 'draft', opts = ['short', 'medium', 'long'];
  return `<div class="card noprint"><div class="row sp"><h2>Learning pathway</h2><span class="pill ${pw.status === 'shared' ? 'ok' : pw.status === 'sent' ? 'warn' : pw.status === 'final' ? 'ok' : ''}">${esc(PW_STATUS_NAME[pw.status])}</span></div>
    <div class="grid g3" style="margin-top:8px">${opts.map(k => { const o = pw.options[k]; return `<label class="sub stack"><div class="row sp"><b>${esc(o.label)}</b><span class="muted small">~${o.weeks}w</span></div>
      ${editable ? `<label class="chk"><input type="radio" name="pwSel" data-a="pwSelect" data-id="${att.id}" data-k="${k}" ${pw.selected === k ? 'checked' : ''}><span>Use this option</span></label>` : (pw.selected === k ? '<span class="pill ok">Selected</span>' : '')}
      <p class="small">${esc(o.headline)}</p><ol class="ul small">${o.steps.map(s => `<li>${esc(s)}</li>`).join('')}</ol></label>`; }).join('')}</div>
    ${editable ? field('Notes for the administrator or parent (optional)', `<textarea data-a="pwNote" data-id="${att.id}">${esc(pw.notes || '')}</textarea>`) : (pw.notes ? `<p class="small muted" style="margin-top:10px"><b>Note:</b> ${esc(pw.notes)}</p>` : '')}
    <div class="row" style="margin-top:12px;flex-wrap:wrap">
      ${editable ? `<button class="btn ghost" data-a="pwGen" data-id="${att.id}">Regenerate options</button><button class="btn teal" data-a="pwFinalize" data-id="${att.id}" ${pw.selected ? '' : 'disabled'}>Finalise</button>` : ''}
      ${pw.status === 'final' ? `<button class="btn teal" data-a="pwSend" data-id="${att.id}">Send to administrator</button>` : ''}
      ${pw.status === 'sent' && isAdmin ? `<button class="btn teal" data-a="pwShare" data-id="${att.id}">Share with parent</button>` : ''}
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
  return `<div class="card"><h2>Learning pathway</h2><p class="muted small" style="margin-bottom:10px">${pw.status === 'shared' ? 'Shared with the parent.' : pw.status === 'sent' ? 'Sent to the administrator for review.' : 'Finalised by the teacher.'}</p>
    ${opt ? `<div class="rec"><div class="eyebrow">${esc(opt.label)} pathway · about ${opt.weeks} weeks</div><b>${esc(opt.headline)}</b><ol class="ul" style="margin-top:8px">${opt.steps.map(s => `<li>${esc(s)}</li>`).join('')}</ol></div>`
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
    opt.steps.forEach((s, i) => { const lines = doc.splitTextToSize(`${i + 1}. ${s}`, 180); if (y > 270) { doc.addPage(); y = 20; } doc.text(lines, 14, y); y += lines.length * 6 + 2; });
  } else { doc.text('No pathway option has been selected yet.', 14, y); y += 8; }
  if (pw.notes) { if (y > 260) { doc.addPage(); y = 20; } y += 4; doc.setFontSize(11); doc.text(doc.splitTextToSize('Note: ' + pw.notes, 180), 14, y); }
  doc.save(`brainova-pathway-${pwFileStem(att)}.pdf`);
}
