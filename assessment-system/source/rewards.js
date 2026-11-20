/* ===================== REWARDS =====================
   A celebratory picture and confetti when a student submits a test,
   chosen by score band. Purely a comfort feature: never affects
   scoring, placement or the report. Admins can upload their own
   picture per band; otherwise a built-in piece of SVG art is used. */
const RW_BANDS = [
  { key: 'sprout', min: 0, name: 'Great effort', icon: 'sprout' },
  { key: 'badge', min: 0.4, name: 'Nice work', icon: 'badge' },
  { key: 'star', min: 0.6, name: 'Well done', icon: 'star' },
  { key: 'medal', min: 0.8, name: 'Excellent', icon: 'medal' },
  { key: 'trophy', min: 0.95, name: 'Outstanding', icon: 'trophy' }
];
const RW_DEFAULT = { enabled: true, confetti: true, images: {} };
const RW_ART = {
  sprout: '<svg width="70" height="70" viewBox="0 0 24 24"><path d="M12 21V11" stroke="#1F6D4A" stroke-width="2" stroke-linecap="round"/><path d="M12 13c0-3.5 3-5 6-5 0 3.5-2.5 6-6 6" fill="#86EFAC" stroke="#1F6D4A" stroke-width="1.2"/><path d="M12 15c0-3-2.5-5-6-5 0 3.5 2.5 6 6 6" fill="#4ADE80" stroke="#1F6D4A" stroke-width="1.2"/></svg>',
  badge: '<svg width="70" height="70" viewBox="0 0 24 24"><path d="M12 2l3 2 3.4-.4.6 3.4 2.6 2.2-2.6 2.2-.6 3.4-3.4-.4-3 2-3-2-3.4.4-.6-3.4L2.4 9.2 5 7l.6-3.4L9 4z" fill="#FBBF24" stroke="#B45309" stroke-width="0.8"/><circle cx="12" cy="9.6" r="2.6" fill="#fff"/></svg>',
  star: '<svg width="70" height="70" viewBox="0 0 24 24"><path d="M12 2l2.9 6.3 6.9.7-5.2 4.6 1.6 6.8-6.2-3.6-6.2 3.6 1.6-6.8-5.2-4.6 6.9-.7z" fill="#F59E0B" stroke="#B45309" stroke-width="0.6"/></svg>',
  medal: '<svg width="70" height="70" viewBox="0 0 24 24"><path d="M7 2l5 6-5 4L2 6z" fill="#EF4444"/><path d="M17 2l-5 6 5 4 5-6z" fill="#3B82F6"/><circle cx="12" cy="15" r="6.5" fill="#FDE68A" stroke="#B45309" stroke-width="1"/><circle cx="12" cy="15" r="3.6" fill="#FBBF24"/></svg>',
  trophy: '<svg width="70" height="70" viewBox="0 0 24 24"><path d="M7 3h10v6a5 5 0 0 1-10 0z" fill="#FBBF24" stroke="#B45309" stroke-width="0.8"/><path d="M7 4H4a3 3 0 0 0 3 5M17 4h3a3 3 0 0 1-3 5" fill="none" stroke="#B45309" stroke-width="1.2"/><rect x="10" y="14" width="4" height="4" fill="#B45309"/><rect x="7" y="18" width="10" height="2.4" rx="1" fill="#92400E"/></svg>'
};
function rwBand(att) {
  const an = AN(att), pct = an.maxMarks ? an.marks / an.maxMarks : 0;
  let band = RW_BANDS[0]; RW_BANDS.forEach(b => { if (pct >= b.min) band = b; }); return band;
}
function rewardBlock(att) {
  const rw = S.settings.rewards; if (!rw || !rw.enabled) return '';
  const band = rwBand(att), img = rw.images && rw.images[band.key];
  return `<div aria-hidden="true">${img ? `<img src="${esc(img)}" alt="" style="max-width:120px;max-height:120px">` : RW_ART[band.icon]}</div><div class="small" style="font-weight:700;margin-top:4px">${esc(band.name)}</div>`;
}
function rewardFx() {
  const rw = S.settings.rewards; if (!rw || !rw.enabled || !rw.confetti) return;
  try {
    const c = document.createElement('canvas'); c.width = innerWidth; c.height = innerHeight;
    c.style.cssText = 'position:fixed;inset:0;pointer-events:none;z-index:9999';
    document.body.appendChild(c);
    const ctx = c.getContext('2d'), colors = ['#5420A8', '#14B8A6', '#F59E0B', '#EF4444', '#3B82F6'];
    const pieces = Array.from({ length: 140 }, () => ({ x: Math.random() * c.width, y: -20 - Math.random() * c.height * 0.3, r: 4 + Math.random() * 5, col: colors[Math.floor(Math.random() * colors.length)], vy: 2 + Math.random() * 3, vx: -1.5 + Math.random() * 3, rot: Math.random() * Math.PI, vr: -0.2 + Math.random() * 0.4 }));
    let t = 0; const dur = 2600;
    function frame() {
      t += 16; ctx.clearRect(0, 0, c.width, c.height);
      pieces.forEach(p => { p.x += p.vx; p.y += p.vy; p.rot += p.vr; ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot); ctx.fillStyle = p.col; ctx.fillRect(-p.r / 2, -p.r / 2, p.r, p.r * 0.6); ctx.restore(); });
      if (t < dur) requestAnimationFrame(frame); else c.remove();
    }
    requestAnimationFrame(frame);
  } catch (e) { /* confetti is a visual extra, never worth failing the submit over */ }
}
function rewardCard() {
  const rw = S.settings.rewards;
  return `<div class="card"><h2>Rewards</h2><p class="muted small" style="margin-bottom:10px">A celebratory picture and confetti when a student submits a test. Never affects scoring or placement.</p>
    <div class="row">${chk('settings.rewards.enabled', rw.enabled, 'Show a reward on submission', true)}${chk('settings.rewards.confetti', rw.confetti, 'Confetti animation', true)}</div>
    <div class="grid g3" style="margin-top:12px">${RW_BANDS.map(b => `<div class="sub stack" style="align-items:center;text-align:center">
      <div style="width:70px;height:70px">${rw.images && rw.images[b.key] ? `<img src="${esc(rw.images[b.key])}" style="max-width:100%;max-height:100%">` : RW_ART[b.icon]}</div>
      <b class="small">${esc(b.name)}</b><span class="muted small">${Math.round(b.min * 100)}%+</span>
      <label class="btn sm ghost" style="cursor:pointer">Upload image<input type="file" accept="image/*" hidden data-a="rwUpload" data-k="${b.key}"></label>
      ${rw.images && rw.images[b.key] ? `<button class="btn sm ghost" data-a="rwClear" data-k="${b.key}">Remove</button>` : ''}</div>`).join('')}</div></div>`;
}
