# Deploying the Google Sheets backend

You do this part yourself — it needs your Google account, which I can't touch. It's about 10 minutes, once.

## 1. Create the Sheet

1. Go to [sheets.google.com](https://sheets.google.com) → **Blank spreadsheet**.
2. Rename it to something like **Brainova Assessment Data** (top-left, where it says "Untitled spreadsheet").
3. Leave it otherwise empty — the script creates its own tabs (students, assessments, attempts, etc.) the first time data is saved. You don't need to set anything up by hand.

## 2. Paste in the script

1. In that Sheet: **Extensions** menu → **Apps Script**. A new tab opens with a code editor already connected to this Sheet.
2. You'll see a file called `Code.gs` with a little placeholder function in it. Select everything in that editor (click inside it, `Ctrl+A`) and delete it.
3. Open `assessment-system/backend/Code.gs` from this project, copy its entire contents, and paste it into that empty editor.
4. Save (`Ctrl+S`, or the little disk icon). You can rename the project at the top (e.g. "Brainova Assessment Backend") if you like — it doesn't matter functionally.

## 3. Set your secret token

This is the password that lets the app talk to your Sheet. You make it up; you never tell it to me.

1. In the Apps Script editor, click the **gear icon** (Project Settings) on the left sidebar.
2. Scroll down to **Script Properties** → **Add script property**.
3. Property: `API_TOKEN`. Value: any long random string — for example, mash the keyboard, or use a password generator, something like `b7x9-k2mQ-v4Lp-9rT3`. Length and randomness are what keep it safe; it doesn't need to be memorable since you'll only ever paste it, not type it.
4. Click **Save script properties**.
5. Write that value down somewhere private (a password manager, a note to yourself) — you'll paste the *same* value into the app's Settings page in step 5 below.

## 4. Deploy it as a web app

1. Back in the Apps Script editor, top-right **Deploy** button → **New deployment**.
2. Click the gear/type icon next to "Select type" → choose **Web app**.
3. Fill in:
   - Description: anything, e.g. "v1"
   - Execute as: **Me** (your account)
   - Who has access: **Anyone**
4. Click **Deploy**.
5. It will ask you to authorize it. Click **Authorize access** → pick your Google account → you'll see an "unverified app" warning (normal — it's your own script, Google just hasn't reviewed it) → click **Advanced** → **Go to [project name] (unsafe)** → **Allow**.
6. You'll now see a **Web app URL** ending in `/exec`. Copy it.

## 5. Connect the app to it

1. Open the Brainova assessment app in your browser, as **Administrator** — either `https://your-site.example/assessment-system/brainova-demo.html` once it's deployed (see "Where it's hosted" below), or the local `brainova-demo.html` file directly while testing.
2. Go to **Settings** → **Google Sheets backend**.
3. Paste the Web App URL into **Web App URL**, and the same secret you made up in step 3 into **Shared token**.
4. Click **Save**, then **Test connection**. It should say "Connected."

**Tell me the Web App URL once you have it — I need it for the next steps (the LMS login link and showing results on student profiles). Don't send me the token; keep that between the Script Properties page and the app's Settings page.**

## If you ever edit Code.gs again

Saving the script editor is not enough to make a change live — you deployed a specific *version*. To update:

1. **Deploy** → **Manage deployments**.
2. Click the pencil (edit) icon on the existing deployment.
3. Under **Version**, choose **New version** → **Deploy**.

This keeps the same URL (so nothing else needs to change) but makes it run your edited code. If you forget this step, the URL keeps serving the old code and it'll look like your edit "didn't work."

## 6. Turn on real per-person access control

You already did steps 1–5 once and sent me the Web App URL — this part
is new, and it's the same "edit Code.gs again" update as above, plus
one more secret. It makes a signed-in student only ever see their own
(released) results, a parent only their own child's, and a teacher/
admin see everything — instead of everyone sharing one all-or-nothing
password.

1. Re-open the Apps Script editor for this project (same Sheet →
   **Extensions** → **Apps Script**).
2. Select everything in `Code.gs` and replace it with the current
   contents of `assessment-system/backend/Code.gs` from this project —
   it's grown a new `authenticate_`/`verifyAuthToken_` section, the rest
   is the same file as before.
3. **Project Settings** (gear icon) → **Script Properties** → **Add
   script property**. Property: `AUTH_SECRET`. Value: another long
   random string, different from your `API_TOKEN` — same rules as
   before, mash the keyboard, you'll only ever paste it.
4. **Deploy** → **Manage deployments** → pencil icon → **Version: New
   version** → **Deploy**, exactly like the section above. The URL
   stays the same.
5. Tell me you've done this (not the value) — I'll need the *same*
   `AUTH_SECRET` value pasted into the Laravel app's live `.env` as
   `ASSESSMENT_SYSTEM_AUTH_SECRET`, alongside `ASSESSMENT_SYSTEM_SHEETS_URL`
   (the same Web App URL from step 4 above — not secret, just the
   address).

Nothing breaks if you skip this for now — until `AUTH_SECRET` exists on
both sides, every request still falls back to the one shared `API_TOKEN`
the way it's worked since Step 3.

## 7. Where it's hosted

Nothing for you to upload by hand: `node build.js` writes the built
file straight into this Laravel app's own `public/assessment-system/`
folder, so a normal git deploy serves it automatically at
`https://your-site.example/assessment-system/brainova-demo.html` -
no separate hosting step, no `ASSESSMENT_SYSTEM_URL` needed unless you
ever want it to live somewhere else.

Opening the LMS sidebar's **Assessment System** link goes through that
same address, with the login token and Sheet URL attached - that's the
one to actually use day to day, not the bare link above.

## 8. Show a student's result on their LMS profile

Same "edit Code.gs again, add one Script Property" pattern as section 6,
for the piece that reads a result back OUT: a student's own profile
page (Student Info → that student → the new **Assessment** tab) now
shows their latest released result, pulled straight from the Sheet.

1. Re-paste `Code.gs` the same way as section 6 (it's grown a `report`
   action) and deploy a **New version**, same clicks as before.
2. Add one more Script Property: `REPORT_TOKEN` — another long random
   value, different from `API_TOKEN` and `AUTH_SECRET`. This one is
   read-only and never reaches a browser; only your Laravel server uses
   it, to ask "what's this one student's latest released result."
3. Tell me once it's deployed — I'll need that value as
   `ASSESSMENT_SYSTEM_REPORT_TOKEN` in the live `.env`.

Until that's set, the Assessment tab on a student's profile just shows
"No released assessment result yet" - nothing breaks either way.

## 9. Replacing the HTML file in the future

If you ever get a redesigned or rebuilt version of the assessment tool
from somewhere else (a different developer, a newer export, anything),
here's exactly what does and doesn't need to happen - written down so
this never requires re-deriving.

**Three separate layers, only one of them is "the HTML":**

1. **The Google Sheet + Apps Script** (Steps 1–6, 8 above) - your data,
   your three secrets (`API_TOKEN`, `AUTH_SECRET`, `REPORT_TOKEN`), the
   actual storage. This never changes just because the HTML changes.
   You will not create a new Sheet, a new Apps Script, or a new secret
   for this.
2. **The LMS side** (`AssessmentSystemController`, the sidebar links,
   the Student profile tab) - also untouched. It talks to whichever
   HTML file `ASSESSMENT_SYSTEM_URL` points at; it doesn't care what's
   inside that file.
3. **The HTML file itself** - this is the only swappable part. A brand
   new file doesn't automatically know how to talk to your Sheet or
   understand the LMS's login link - that wiring (`source/api.js` and
   `source/identity.js` in this project) has to be added into whatever
   new file you get, the same way it was added to this one.

**What actually happens when you hand me a new HTML file:**

1. You give me the file (paste it, or its location).
2. I add the same two pieces of wiring into it - no new Google account
   steps, no new Script Properties, nothing you have to click through
   on the Google side at all. If its internal structure is very
   different from this one, this step might take real work on my end,
   but it is still just *wiring*, never new infrastructure.
3. I rebuild and push, same as every change so far.
4. You do the exact same three clicks you already know: **Update from
   Remote** → **Deploy HEAD Commit** → `/optimize`.
5. To check it actually reconnected, use the **Test connection** button
   already on the Settings page (Google Sheets backend card) - that's
   already the "just check and click" confirmation, no new tool needed.

So in short: swapping the HTML is a request to me, not a new setup
project for you. The Apps Script/secrets ceremony in sections 1–6 and 8
above is a one-time thing, done once, done already.
