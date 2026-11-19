# Brainova: handoff to a new chat for LMS integration

## What to do
1. Open a new chat with Claude (Claude Code or a chat with file upload and code tools works best).
2. Upload this whole folder (or the zip). If you can only attach a few files, attach: `README-HANDOFF.md`, `original-brief.txt`, `lms-spec/*`, `source/engine.js` and `source/core.js`.
3. Copy the prompt at the bottom of this file into the chat. Fill the blanks in the LMS section first.
4. Also give Claude access to your LMS code (repository, a zip, or the folders for users, roles, courses and routes). Without it, it can only write a generic kit.

## What is in the pack
| File | What it is |
|---|---|
| `brainova-demo.html` | The working demo (one file). Open it in a browser to see every screen. |
| `original-brief.txt` | The original project brief. |
| `source/engine.js` | Pure logic: question bank, fixed and adaptive engine, grading, analysis, placement. Runs in Node, so it can be reused on the server. |
| `source/core.js` | State, sample data, report sharing defaults, helpers. |
| `source/views_a.js`, `views_b.js`, `views_c.js`, `views_run.js` | Screens: dashboard, builder, bank, results, reports, student runner, live class, exports, settings. |
| `source/actions.js`, `style.css`, `build.js` | Click handling, styling, and the script that builds the single HTML file (`node build.js`). |
| `source/test.js` | Engine tests. |
| `lms-spec/laravel-kit.md` | Draft migrations, routes, Repository Pattern and security notes. |
| `lms-spec/endpoints-and-data.md` | The API endpoint map, field mapping and JSON shapes. |

## What the demo already does
- Grades 1-10 and short courses; assessment builder; fixed (Strengthen / Hard / Extend) and adaptive tests.
- Teacher-led live mode and independent timed mode with parent supervision.
- Question bank with import and AI drafting; analytics, topic levels, error analysis, placement.
- Reports for Admin, Teacher, Parent and Student, with a settings table for what each sees.
- CSV and HTML exports.

## What is demo-only and must change for real use
| Demo | Production |
|---|---|
| Data saved in the browser (localStorage) | MySQL through Laravel repositories |
| Role switcher, no sign-in | LMS login, roles and permissions |
| Answer keys live in the browser | Keys stay on the server; the browser gets questions only |
| Adaptive engine in the browser | Same logic on the server |
| One-screen live class with simulated classmates | WebSockets (Laravel Reverb) so real devices join |
| AI drafting through Claude in the viewer | Server-side queued job, teacher approval before use |
| Timer in the browser | Server-side timers and audit log |
| PDF = downloaded HTML | Server PDF (DomPDF or Snappy) |
| Seeded sample students | Real accounts through the LMS endpoints |

## Things to decide before you start (fill in)
- LMS: Laravel version ______  PHP ______  Database ______
- Front end in the LMS: Blade + Bootstrap ___  jQuery ___  (Brainova screens must be rewritten in this stack)
- Sign-in and roles package (e.g. spatie/laravel-permission, Sanctum, Passport): ______
- Do students, parents, teachers, courses and syllabus already exist in the LMS DB? Table names: ______
- Hosting and queue/WebSocket support: ______
- Which report details parents and teachers may see (defaults are in `source/core.js`, `DEFAULT_SHARING`): keep / change ______

## Prompt to paste into the new chat

> I have a working demo of "Brainova Intelligent Assessment & Placement System" (files attached). I want to integrate it into my existing LMS, which runs on Laravel, Blade, Bootstrap and jQuery, using the Repository Pattern.
>
> Read `README-HANDOFF.md`, `original-brief.txt`, everything in `lms-spec/`, then `source/engine.js` and `source/core.js`. My LMS code is attached/available at: [PATH OR REPO].
>
> Work in this order, and stop after each step to show me what you did:
> 1. Study my LMS (users, roles, courses, grades, routes, layout) and tell me what you found and any gaps in the kit.
> 2. Write the database migrations and models for questions, assessments, assignments, attempts, responses, topic results, notes, supervision records, settings and report-sharing rules, linked to my existing student, parent, teacher and course tables.
> 3. Build repositories and services. Port `engine.js` logic (grading, fixed assembly, adaptive selection, analysis, placement) to PHP or run it as a Node service, and keep answer keys on the server only.
> 4. Build the controllers, policies and API endpoints from `lms-spec/endpoints-and-data.md`, with LMS sign-in and role checks. Add server-side timers and an audit log.
> 5. Rebuild the screens in Blade, Bootstrap and jQuery: builder, question bank, assign and attempts, student test runner, results, and the four report versions with the sharing settings. Keep the brand colours: navy #17233B, purple #5420A8, turquoise #14B8A6.
> 6. Add exports (CSV, PDF) and the live class mode with WebSockets.
> 7. Write tests and a deployment checklist.
>
> Rules: do not claim anti-cheating protection the system cannot give; no webcam or screen spying; response time must never lower a student's score; parents and students see results only after the teacher releases them; do not make destructive changes to my existing LMS tables; ask me before changing any existing table.

## Notes
- Keep the demo's proficiency logic: levels come from an ability estimate (not raw %); cut-offs are in `DEFAULT_LEVELS` in `engine.js`.
- A student backup file (`brainova-backup.json`) from the demo contains real names and results if you used real data. Do not upload it unless you mean to.
