/* ===================== LMS IDENTITY (signed login handoff) =====================
   When the Brainova LMS opens this app, it appends a short-lived signed
   token to the URL (?t=...) instead of leaving the old role switcher on
   screen. This file only reads the token's own claims (uid/role/name/exp)
   to know who is using the app, and hands the raw token to the Google
   Sheets backend (api.js) on every request from here on.

   It does NOT verify the signature - there is no secret in the browser
   to verify it with, by design (see backend/Code.gs's own header comment).
   That verification is Step 5's job, on the server. Until Step 5 lands,
   treat this as "who the LMS says is using it", not yet a security
   boundary - the Sheet itself is still only gated by the one shared
   token from Step 3. When no token is present (or it's missing/expired),
   the app falls back to the old role switcher so it stays testable on
   its own without the LMS. */
const LMS_SESSION_KEY = 'brainova.lmsidentity.v1';

function b64urlDecode_(s) {
  s = s.replace(/-/g, '+').replace(/_/g, '/');
  while (s.length % 4) s += '=';
  return decodeURIComponent(atob(s).split('').map(c => '%' + c.charCodeAt(0).toString(16).padStart(2, '0')).join(''));
}

function parseLmsToken_(token) {
  try {
    const payload = token.split('.')[0];
    if (!payload) return null;
    const claims = JSON.parse(b64urlDecode_(payload));
    if (!claims || !claims.role || !claims.uid || !claims.exp) return null;
    if (claims.exp * 1000 < Date.now()) return null;
    return claims;
  } catch (e) { return null; }
}

// The LMS puts the token on the URL only once, on handoff. It's kept in
// sessionStorage (not localStorage) from then on, so it survives a page
// refresh inside that same tab/iframe but never lingers once it's closed.
function readLmsToken_() {
  const fromUrl = new URLSearchParams(location.search).get('t');
  if (fromUrl) { try { sessionStorage.setItem(LMS_SESSION_KEY, fromUrl); } catch (e) { /* ignore */ } return fromUrl; }
  try { return sessionStorage.getItem(LMS_SESSION_KEY); } catch (e) { return null; }
}

// Called once at startup. Returns true when a real LMS identity takes
// over (role switcher stays hidden); false when the app is on its own.
function applyLmsIdentity() {
  const token = readLmsToken_();
  const claims = token && parseLmsToken_(token);
  if (!claims) { U.lms = null; return false; }

  // The LMS sends uid/children as real numbers (Laravel's own integer
  // ids). Every id already living in this app - demo data, DOM data-id
  // attributes read off a click, you name it - is a string, and a
  // number never === a string in JS, so every lookup and every
  // assign-to-student checkbox would silently fail to match a real
  // student/parent if this stayed a number. Normalize once, here, so
  // nothing downstream has to know this distinction exists.
  const uid = String(claims.uid);
  const children = Array.isArray(claims.children) ? claims.children.map(String) : null;

  U.lms = { token, uid, role: claims.role, name: claims.name || '' };
  U.role = claims.role;
  U.view = HOME[claims.role] || U.view;

  // Registering this person's own roster row (below) uses saveSelf(),
  // not save()'s full scheduleSync - a signed-in student/parent's
  // browser is only ever allowed to write their OWN row on the backend
  // (see Code.gs's writeAllowed_), never a full administrative snapshot.
  if (claims.role === 'student') {
    U.studentId = uid;
    // First time this LMS student opens the tool: give them a minimal
    // row so every existing "find the student" lookup has something to
    // find. Name comes from the token; grade/accommodations stay blank
    // until an admin fills them in on the Students page, or a later
    // sync pass maps the LMS's own class/grade data onto this field.
    if (!S.students.find(s => s.id === uid)) {
      const rec = { id: uid, name: claims.name || 'Student', grade: null };
      S.students.push(rec);
      persistLocal();
      if (window.BrainovaAPI) window.BrainovaAPI.saveSelf('students', rec);
    }
  }
  if (claims.role === 'parent') {
    U.parentId = uid;
    const existing = S.parents.find(p => p.id === uid);
    if (!existing) {
      const rec = { id: uid, name: claims.name || 'Parent', childIds: children || [] };
      S.parents.push(rec);
      persistLocal();
      if (window.BrainovaAPI) window.BrainovaAPI.saveSelf('parents', rec);
    } else if (children) {
      existing.childIds = children;
      persistLocal();
      if (window.BrainovaAPI) window.BrainovaAPI.saveSelf('parents', existing);
    }
  }

  if (window.BrainovaAPI) {
    window.BrainovaAPI.setAuthToken(token);
    // The LMS hands every device the (non-secret) Sheet URL on login,
    // so a student's own phone/laptop gets wired up the first time they
    // open this, without anyone manually visiting Settings on it. Only
    // the URL travels this way - never the shared token (see
    // AssessmentSystemController::launch() on the LMS side).
    const sheetsUrl = new URLSearchParams(location.search).get('su');
    if (sheetsUrl) window.BrainovaAPI.configure({ url: sheetsUrl });
  }
  return true;
}
