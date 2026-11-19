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

  U.lms = { token, uid: claims.uid, role: claims.role, name: claims.name || '' };
  U.role = claims.role;
  U.view = HOME[claims.role] || U.view;

  if (claims.role === 'student') {
    U.studentId = claims.uid;
    // First time this LMS student opens the tool: give them a minimal
    // row so every existing "find the student" lookup has something to
    // find. Name comes from the token; grade/accommodations stay blank
    // until an admin fills them in on the Students page, or a later
    // sync pass maps the LMS's own class/grade data onto this field.
    if (!S.students.find(s => s.id === claims.uid)) {
      S.students.push({ id: claims.uid, name: claims.name || 'Student', grade: null });
      save();
    }
  }
  if (claims.role === 'parent') {
    U.parentId = claims.uid;
    const existing = S.parents.find(p => p.id === claims.uid);
    if (!existing) {
      S.parents.push({ id: claims.uid, name: claims.name || 'Parent', childIds: claims.children || [] });
      save();
    } else if (claims.children) {
      existing.childIds = claims.children;
      save();
    }
  }

  if (window.BrainovaAPI) window.BrainovaAPI.setAuthToken(token);
  return true;
}
