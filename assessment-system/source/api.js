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
  const SENT_KEY = 'brainova.lastsent.v1';
  const SNAPSHOT_COLLECTIONS = ['students', 'parents', 'assessments', 'bank', 'simpleSets', 'settings', 'supervision', 'pathways'];

  let cfg = { url: '', token: '' };
  try { Object.assign(cfg, JSON.parse(localStorage.getItem(CFG_KEY) || '{}')); } catch (e) { /* ignore */ }

  // The signed per-user token from the LMS handoff (see identity.js).
  // Deliberately kept in memory only, never in the persisted cfg above -
  // it's short-lived by design, so caching it across visits would just
  // mean resending a stale one. Set via setAuthToken() on every login.
  let authToken = '';

  let queue = [];
  try { queue = JSON.parse(localStorage.getItem(QUEUE_KEY) || '[]'); } catch (e) { /* ignore */ }

  // The JSON text of each collection as it stood the last time this
  // browser successfully wrote it to the Sheet. scheduleSync() diffs
  // against this so an edit to one setting doesn't drag all ~450
  // question-bank rows (or the whole roster) along for the ride every
  // time - before this, EVERY save() resynced EVERY collection in
  // full, which made a routine snapshot write slow enough to risk
  // tripping the script lock or the client's own timeout.
  let lastSent = {};
  try { lastSent = JSON.parse(localStorage.getItem(SENT_KEY) || '{}'); } catch (e) { /* ignore */ }

  let flushing = false, syncTimer = 0, retryTimer = 0;

  function isConfigured() { return !!cfg.url; }
  function persistQueue() { try { localStorage.setItem(QUEUE_KEY, JSON.stringify(queue)); } catch (e) { /* ignore */ } }
  function persistSent() { try { localStorage.setItem(SENT_KEY, JSON.stringify(lastSent)); } catch (e) { /* ignore */ } }

  function configure(opts) {
    cfg = Object.assign({}, cfg, opts || {});
    try { localStorage.setItem(CFG_KEY, JSON.stringify(cfg)); } catch (e) { /* ignore */ }
    flush();
  }

  // Apps Script's doPost doesn't need a CORS preflight as long as the
  // request body is sent as text/plain, so that's what every write uses.
  // A hard timeout matters here specifically because a slow write (a
  // big snapshot, or Apps Script itself being slow to respond) would
  // otherwise leave flush() waiting forever on this one fetch, with
  // flushing staying true and every later retry silently doing nothing -
  // the queue would look permanently "stuck" with no error ever shown.
  async function post(body) {
    const ctrl = typeof AbortController !== 'undefined' ? new AbortController() : null;
    const timer = ctrl ? setTimeout(() => ctrl.abort(), 30000) : 0;
    let res;
    try {
      res = await fetch(cfg.url, {
        method: 'POST',
        headers: { 'Content-Type': 'text/plain;charset=utf-8' },
        body: JSON.stringify(Object.assign({ token: cfg.token, authToken }, body)),
        signal: ctrl ? ctrl.signal : undefined
      });
    } catch (e) {
      if (e && e.name === 'AbortError') throw new Error('timed out waiting for a response');
      throw e;
    } finally { clearTimeout(timer); }
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

  // A stuck sync used to fail completely silently - the Settings page's
  // "N change(s) waiting to sync" pill was the only clue something was
  // wrong, with no way to see why. Surfacing the actual error (once per
  // distinct message, not on every 8s retry) is what makes this
  // diagnosable at all instead of a guessing game.
  let lastSyncError_ = '';
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
          lastSyncError_ = '';
          // Record exactly what this item actually sent (not the current
          // live S, which may have moved on already) as the new baseline,
          // so the next scheduleSync() only resends what changed after this.
          if (item.kind === 'snapshot' && item.payload) {
            Object.keys(item.payload).forEach(k => { lastSent[k] = JSON.stringify(item.payload[k]); });
            persistSent();
          }
        } catch (e) {
          const msg = (e && e.message) || 'unknown error';
          if (msg !== lastSyncError_) {
            lastSyncError_ = msg;
            if (typeof toast === 'function') toast('Could not save a change to the Sheet: ' + msg);
          }
          // 'forbidden'/'unauthorized' means the backend's permission rules
          // will never allow THIS item, no matter how many times it's
          // retried (e.g. a stray full-snapshot write queued by a student
          // account, which students are never allowed to send) - unlike a
          // network hiccup or a busy lock, retrying it changes nothing. Left
          // at the front of the queue, a single one of these would silently
          // block every legitimate write behind it (including real test
          // submissions) forever, since nothing else in the queue is ever
          // tried until item 1 succeeds. Drop it and move on instead.
          if (msg === 'forbidden' || msg === 'unauthorized') { queue.shift(); persistQueue(); continue; }
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
  // request per keystroke. save() calls this on almost any edit
  // anywhere in the app, with no idea which collection actually
  // changed - so this only ever sends the collections whose content
  // is actually different from what was last successfully written,
  // instead of re-sending everything (including the ~450-row question
  // bank) on every single edit.
  function scheduleSync(S) {
    if (!isConfigured()) return;
    clearTimeout(syncTimer);
    syncTimer = setTimeout(() => {
      const full = buildSnapshot(S), changed = {};
      Object.keys(full).forEach(k => {
        const text = JSON.stringify(full[k]);
        if (text !== lastSent[k]) changed[k] = full[k];
      });
      if (Object.keys(changed).length) enqueue('snapshot', changed);
    }, 1000);
  }

  // Every collection becomes an array of {id, ...} records, even the two
  // that aren't naturally arrays in the app's own state - that way the
  // backend only ever needs one generic "upsert these records" routine.
  function buildSnapshot(S) {
    const out = {};
    SNAPSHOT_COLLECTIONS.forEach(k => {
      const v = S[k];
      if (k === 'pathways') out[k] = (S.attempts || []).filter(a => a.pathways).map(a => ({ id: a.id, json: JSON.stringify(a.pathways) }));
      else if (Array.isArray(v)) out[k] = v;
      else if (k === 'settings' && v !== undefined) out[k] = [{ id: 'main', json: JSON.stringify(v) }];
      else if (k === 'supervision' && v && typeof v === 'object') out[k] = Object.keys(v).map(id => Object.assign({ id }, v[id]));
    });
    return out;
  }

  // Immediate, per the brief: "save each attempt and its responses as
  // soon as it is submitted (and after every answer in adaptive tests)".
  function saveAttempt(att, analysis) { enqueue('attempt', { attempt: att, analysis: analysis || null }); }
  function saveResponse(attemptId, resp, meta) { enqueue('response', Object.assign({ attemptId, resp }, meta || {})); }
  // Narrower than scheduleSync's full snapshot: upserts exactly one
  // record in one collection. This is the only write kind the backend
  // lets a signed-in student/parent make outside their own attempts -
  // used by identity.js to register their own roster row on first
  // login, without ever letting their browser touch anyone else's data.
  function saveSelf(collection, record) { enqueue('self', { collection, record }); }

  async function fetchSnapshot() {
    if (!isConfigured()) return null;
    try {
      const url = cfg.url + '?action=bootstrap&token=' + encodeURIComponent(cfg.token || '') + '&authToken=' + encodeURIComponent(authToken || '');
      const res = await fetch(url);
      if (!res.ok) return null;
      const data = await res.json();
      return (data && data.ok !== false && data.state) ? data.state : null;
    } catch (e) { return null; }
  }

  // Called once after a fresh fetchSnapshot() replaces S wholesale (see
  // init() in actions.js) - without this, the very next edit would see
  // every collection as "changed" relative to this browser's old
  // lastSent baseline and resend all of them once, even though nothing
  // local actually changed, just to catch up with what the Sheet
  // already has.
  function markSynced(S) {
    const full = buildSnapshot(S);
    Object.keys(full).forEach(k => { lastSent[k] = JSON.stringify(full[k]); });
    persistSent();
  }

  function pendingCount() { return queue.length; }
  function getConfig() { return { url: cfg.url, token: cfg.token }; }
  function setAuthToken(t) { authToken = t || ''; }
  // A toast flashes for ~3 seconds and is easy to miss if nobody's
  // watching the screen right when a background retry fires - this lets
  // Settings show the same message any time someone checks, not just
  // the instant it happens.
  function lastSyncError() { return lastSyncError_; }

  flush(); // in case a previous session left queued items waiting for a connection

  window.BrainovaAPI = { configure, isConfigured, getConfig, setAuthToken, scheduleSync, saveAttempt, saveResponse, saveSelf, fetchSnapshot, markSynced, pendingCount, lastSyncError, flush };
})();
