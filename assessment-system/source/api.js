/* ===================== GOOGLE SHEETS API ADAPTER =====================
   Talks to a Google Apps Script web app (wired up in a later step).
   Until BrainovaAPI.configure() is given a real url, every call below
   is a harmless no-op and the app behaves exactly like the
   localStorage-only demo did. Once configured: localStorage is still
   written first on every save() (see core.js) and remains the offline
   cache; everything sent onward also goes through this persisted
   outbox queue, so a dropped connection never loses an attempt or an
   answer - it just waits in localStorage until the network (or the
   next page load) lets it through. */
(function () {
  const CFG_KEY = 'brainova.apiconfig.v1';
  const QUEUE_KEY = 'brainova.outbox.v1';
  const SNAPSHOT_COLLECTIONS = ['students', 'parents', 'assessments', 'bank', 'settings', 'supervision'];

  let cfg = { url: '', token: '' };
  try { Object.assign(cfg, JSON.parse(localStorage.getItem(CFG_KEY) || '{}')); } catch (e) { /* ignore */ }

  let queue = [];
  try { queue = JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { /* ignore */ }

  let flushing = false, syncTimer = 0, retryTimer = 0;

  function isConfigured() { return !!cfg.url; }
  function persistQueue() { try { localStorage.setItem(QUEUE_KEY, JSON.stringify(queue)); } catch (e) { /* ignore */ } }

  function configure(opts) {
    cfg = Object.assign({}, cfg, opts || {});
    try { localStorage.setItem(CFG_KEY, JSON.stringify(cfg)); } catch (e) { /* ignore */ }
    flush();
  }

  // Apps Script's doPost doesn't need a CORS preflight as long as the
  // request body is sent as text/plain, so that's what every write uses.
  async function post(body) {
    const res = await fetch(cfg.url, {
      method: 'POST',
      headers: { 'Content-Type': 'text/plain;charset=utf-8' },
      body: JSON.stringify(Object.assign({ token: cfg.token }, body))
    });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const data = await res.json();
    if (data && data.ok === false) throw new Error(data.error || 'Request rejected');
    return data;
  }

  function enqueue(kind, payload) {
    if (!isConfigured()) return; // nothing to talk to yet - stay a no-op, same as before this adapter existed
    queue.push({ id: 'q_' + Math.random().toString(36).slice(2, 9), kind, payload, ts: Date.now() });
    persistQueue();
    flush();
  }

  async function flush() {
    if (!isConfigured() || flushing || (typeof navigator !== 'undefined' && navigator.onLine === false)) return;
    flushing = true;
    try {
      while (queue.length) {
        const item = queue[0];
        try {
          await post({ action: 'write', kind: item.kind, payload: item.payload });
          queue.shift();
          persistQueue();
        } catch (e) {
          scheduleRetry();
          break; // keep order: don't send item 2 before a stuck item 1 is confirmed
        }
      }
    } finally { flushing = false; }
  }

  function scheduleRetry() {
    clearTimeout(retryTimer);
    retryTimer = setTimeout(flush, 8000);
  }

  if (typeof window !== 'undefined') {
    window.addEventListener('online', flush);
    setInterval(flush, 20000); // safety net in case an 'online' event is missed
  }

  // Debounced: the "administrative" collections (not attempts - those
  // have their own immediate path below) resync a short idle moment
  // after the last change, so typing in a textarea doesn't fire one
  // request per keystroke.
  function scheduleSync(S) {
    if (!isConfigured()) return;
    clearTimeout(syncTimer);
    syncTimer = setTimeout(() => {
      const snapshot = {};
      SNAPSHOT_COLLECTIONS.forEach(k => { if (S[k] !== undefined) snapshot[k] = S[k]; });
      enqueue('snapshot', snapshot);
    }, 1000);
  }

  // Immediate, per the brief: "save each attempt and its responses as
  // soon as it is submitted (and after every answer in adaptive tests)".
  function saveAttempt(att) { enqueue('attempt', att); }
  function saveResponse(attemptId, resp, meta) { enqueue('response', Object.assign({ attemptId, resp }, meta || {})); }

  async function fetchSnapshot() {
    if (!isConfigured()) return null;
    try {
      const url = cfg.url + '?action=bootstrap&token=' + encodeURIComponent(cfg.token || '');
      const res = await fetch(url);
      if (!res.ok) return null;
      const data = await res.json();
      return (data && data.ok !== false && data.state) ? data.state : null;
    } catch (e) { return null; }
  }

  function pendingCount() { return queue.length; }

  flush(); // in case a previous session left queued items waiting for a connection

  window.BrainovaAPI = { configure, isConfigured, scheduleSync, saveAttempt, saveResponse, fetchSnapshot, pendingCount, flush };
})();
