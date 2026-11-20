/* ===================== VOICE-OVER =====================
   Spoken encouragement during a test, using the browser's built-in
   SpeechSynthesis. Purely a comfort feature: never reads a question,
   never reacts to whether an answer was right or wrong, and is easy
   to mute. */
const VOICE_DEFAULT = { enabled: true, voiceURI: '', rate: 1, volume: 1, events: { welcome: true, progress: true, halfway: true, last: true, finish: true } };
const VOICE_MSG = {
  welcome: n => `Hi ${n}. Let's get started. Take your time and read each question carefully.`,
  progress: n => `Good work, ${n}. Keep going.`,
  halfway: n => `You're halfway through, ${n}. You're doing great.`,
  last: n => `This is the last question, ${n}. Finish strong.`,
  finish: n => `Well done, ${n}! Your answers have been saved.`
};
function voiceSupported() { return typeof window !== 'undefined' && 'speechSynthesis' in window; }
function voiceEnabled() { return voiceSupported() && S.settings.voice && S.settings.voice.enabled; }
function voicePick() {
  const cfg = S.settings.voice, list = window.speechSynthesis.getVoices();
  if (cfg.voiceURI) { const v = list.find(v => v.voiceURI === cfg.voiceURI); if (v) return v; }
  return list.find(v => /^en/i.test(v.lang)) || list[0] || null;
}
function voiceUtter(text) {
  const cfg = S.settings.voice;
  try {
    window.speechSynthesis.cancel();
    const u = new SpeechSynthesisUtterance(text), v = voicePick();
    if (v) u.voice = v;
    u.rate = cfg.rate || 1; u.volume = cfg.volume == null ? 1 : cfg.volume;
    window.speechSynthesis.speak(u);
  } catch (e) { /* speech synthesis is best-effort only */ }
}
function voiceSay(key) {
  if (!voiceEnabled()) return;
  const cfg = S.settings.voice; if (cfg.events && cfg.events[key] === false) return;
  const r = U.run; if (!r) return;
  voiceUtter(VOICE_MSG[key]((r.st.name || '').split(' ')[0]));
}
function voiceStart() { voiceSay('welcome'); }
function voiceNext() {
  const r = U.run; if (!r || r.a.model === 'adaptive') return; // progress milestones only make sense for a known, fixed question count
  const n = r.items.length;
  if (r.idx === n - 1) voiceSay('last');
  else if (n > 3 && r.idx === Math.floor(n / 2)) voiceSay('halfway');
  else if (r.idx > 0 && r.idx % 3 === 0) voiceSay('progress');
}
function voiceFinish() { voiceSay('finish'); }
function voiceStop() { if (voiceSupported()) try { window.speechSynthesis.cancel(); } catch (e) { } }
function voiceSample() { if (!voiceSupported()) { toast('Voice-over is not supported in this browser.'); return; } voiceUtter('Hi! This is how the voice-over sounds during a test.'); }
function voiceBtns() {
  if (!voiceSupported()) return '';
  const on = voiceEnabled();
  return `<button class="btn sm ghost" data-a="voiceToggle" title="${on ? 'Mute voice-over' : 'Unmute voice-over'}" aria-label="${on ? 'Mute voice-over' : 'Unmute voice-over'}">${on ? 'Mute voice' : 'Unmute voice'}</button>`;
}
function voiceCard() {
  if (!voiceSupported()) return `<div class="card"><h2>Voice-over</h2><p class="muted">This browser does not support spoken voice-over.</p></div>`;
  const v = S.settings.voice, voices = window.speechSynthesis.getVoices();
  return `<div class="card"><h2>Voice-over</h2><p class="muted small" style="margin-bottom:10px">Spoken encouragement during a test (welcome, progress, halfway, last question, finish). Uses the browser's built-in text-to-speech and never reads questions or judges answers.</p>
    <div class="grid g2">${chk('settings.voice.enabled', v.enabled, 'Enable voice-over', true)}${field('Voice', `<select data-m="settings.voice.voiceURI">${optList(voices.map(x => ({ v: x.voiceURI, l: x.name + ' (' + x.lang + ')' })), v.voiceURI, 'Browser default')}</select>`)}
    ${field('Speaking rate', inp('settings.voice.rate', v.rate, 'number', 'step="0.1" min="0.5" max="2"'))}${field('Volume', inp('settings.voice.volume', v.volume, 'number', 'step="0.1" min="0" max="1"'))}</div>
    <div class="row" style="margin-top:10px;flex-wrap:wrap">${['welcome', 'progress', 'halfway', 'last', 'finish'].map(k => chk('settings.voice.events.' + k, v.events[k], k[0].toUpperCase() + k.slice(1))).join('')}</div>
    <div class="row" style="margin-top:10px"><button class="btn ghost" data-a="voiceTest">Play a sample</button></div></div>`;
}
if (voiceSupported() && 'onvoiceschanged' in window.speechSynthesis) window.speechSynthesis.onvoiceschanged = () => { if (U.view === 'settings') render(); };
