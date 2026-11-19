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

1. Open the Brainova assessment app (`brainova-demo.html`) in your browser, as **Administrator**.
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
   address) and `ASSESSMENT_SYSTEM_URL` (wherever `brainova-demo.html`
   ends up hosted, once that's decided).

Nothing breaks if you skip this for now — until `AUTH_SECRET` exists on
both sides, every request still falls back to the one shared `API_TOKEN`
the way it's worked since Step 3.
