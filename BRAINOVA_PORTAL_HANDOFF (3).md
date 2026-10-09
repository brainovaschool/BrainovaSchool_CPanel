# Brainova Team Portal: handoff for Claude in VS Code

## 1. What to share, and how

Put these two files in the root of your LMS project, in a new folder called `docs/team-portal/`:

```
your-lms/
  docs/team-portal/
    brainova-team-board.html      <- the prototype (reference only, never ship or edit it)
    BRAINOVA_PORTAL_HANDOFF.md    <- this file
  CLAUDE.md                       <- add the "Standing rules" section (part 2) to it
```

Why this format:
- The HTML is the visual and behavioural spec. **It holds two things in one file: the Team Portal (phases 0 to 9) and the admin-only Course Planner (phase 10, built later; sidebar item "Course Planner").** It runs in any browser, and its **Developer Handoff** page and top comment explain the rules.
- This Markdown file tells Claude **how to integrate** without breaking your LMS.
- `CLAUDE.md` is read automatically by Claude Code at the start of every session, so the safety rules are never forgotten between phases.

Do not paste the 250 KB HTML into the chat. Tell Claude to read the file from disk.

---

## 2. Standing rules (copy this section into `CLAUDE.md`)

```md
# Team Portal integration: standing rules

The file docs/team-portal/brainova-team-board.html is a PROTOTYPE of a staff task and performance portal.
It is a specification, not code to copy. Its data is simulated in the browser (the `Api` object and `seedPortal()`).
Read docs/team-portal/BRAINOVA_PORTAL_HANDOFF.md before any work on it.

1. This LMS already exists and is in production. Never break it.
   - Before writing anything, inspect the codebase: framework, language, database and migration tool,
     auth and roles, routing, UI components and styles, the fee module, notification system, file storage, tests.
   - Reuse what exists: the LMS login, user table, role system, layout, components, notification and file upload.
     Do NOT build a second login, a second user table, or a second design system.
   - Match the existing code style, folder structure, naming and test patterns.
2. Additive only.
   - New tables get a prefix: `portal_` (or the LMS's own convention). New routes live under /portal (or equivalent).
   - Never alter or delete existing tables, columns, routes or permissions. A migration may only ADD.
   - Every migration must be reversible and must not touch existing rows.
   - No change to the student, teacher, course, attendance or fee tables except through the documented
     integration point in section "Fee module link".
3. Staff and students are different people. Portal users are staff. Do not mix staff data into student tables.
4. Permissions are enforced on the SERVER on every request. Hiding a button is not security.
5. Work in small phases (see the phase list). One phase per branch and per pull request.
   Stop after each phase, summarise what changed, run the tests, and wait for approval.
6. Ask before guessing. If the LMS has something that overlaps with a feature (tasks, attendance,
   notifications, payroll, roles), say so and propose reuse or a link. Do not silently duplicate.
7. Money: never write to existing fee or accounting records without an explicit, approved design (see Fee module link).
   Use integer amounts in the smallest unit or the LMS's existing money type. Currency is PKR unless the LMS says otherwise.
8. Time zone is Asia/Karachi. Store timestamps in UTC, show them in Asia/Karachi.
9. Keep a CHANGELOG entry and update docs for every phase. Add tests for every business rule.
10. Never commit secrets. Use the LMS's existing config and environment pattern.
11. The Course Planner (phase 10) only PLANS and TRIGGERS. It must reuse the LMS's existing Course, Class or Section,
    Teacher, Timetable and Fee entities and services. Never create a second course, teacher or fee model.
    Build phases 0 to 9 first. Do not start phase 10 until I say so.
```

---

## 3. What the prototype contains

Open the HTML and read the **Developer Handoff** page (admin, left menu). Summary:

| Area | What it does |
|---|---|
| Roles | Admin (owner) and Employee. "Content Coordinator" is not an account type: it is a **responsibility** the admin can give to any employee. |
| Responsibilities | Recurring duties with a person responsible. Two carry permissions: `content` (create, plan, import, review reels) and `audience` (note down views and followers). Admin can transfer any duty. |
| Tasks | Admin assigns. Workflow: Assigned, In progress, Submitted, Under review, Revision (loops), Approved, Completed. Full submission history is kept. |
| Scoring | Final score out of 10 = revision score (from an editable table) + quality score (0 to 5 set by admin). |
| Paid tasks | Open for claiming, or assigned. Claim rule: no overdue task and none in revision. Payment status: unpaid, due, paid. |
| Work log | Day is pre-filled as one-hour rows with a lunch hour. Employee picks from assigned tasks or standard activities. Extra entries allowed. Day locks on submit. |
| Attendance | First login of the day. |
| Notifications | Assigned, urgent, deadline, overdue, revision, approved, feedback, paid task events, payment, responsibility changes. |
| Analytics | Per employee and organisation. |
| Course Planner | Admin only. Plan courses 3 months to 5 years ahead, see setup and delivery progress, check teacher load, then press Create course to build the real course in the LMS. See Part B. |
| Social board | Daily plans, weekly goals, reel pipeline with admin and coordinator review, month plan with category targets, audience numbers, page fixes, growth pay. |

Everything the screens read goes through the `Api` object. **Each method is one backend endpoint.** The suggested endpoint list is on the Handoff page, section 8, and in the exported JSON (Settings, "Export for your AI agent").

---

## 4. Fee module link (money)

I do not know how your fee module is built, so Claude must **inspect it first** and then propose a design for approval. Do not let it start coding the link blind.

What money exists in the portal:
1. **Paid task payouts**: `amount` on a paid task, `payStatus` unpaid, due, paid. This is money going **out** to staff.
2. **Growth pay (variable pay)**: base plus performance bonus per employee per month, from the Growth Pay tab.

Likely design (confirm against the real fee module):
- The fee module is probably about **student fees coming in**. Staff payouts are **expenses**. They must never be recorded as student fee entries.
- If the fee module has a ledger, expense or accounts table: create an expense record when admin marks a paid task as paid, with a reference back to the portal task id. Use its API or service layer, never direct SQL on its tables.
- If it has no expense concept: keep a `portal_payouts` table, and show it on a report, with an export the accountant can use.
- Never change existing fee records. Link by reference id only.
- Idempotent: marking paid twice must not create two ledger entries.
- Auditable: who marked it paid, when, amount, task id.
- Growth pay: compute monthly (base + bonus from milestone completion) into a payable record per employee. Admin approves before it becomes a payout.

Open questions Claude must answer from the code, then ask you:
- Does the fee module have expenses, payroll or a general ledger?
- What is the money type and currency handling?
- Does it already have staff, teachers or salary records to reuse?
- Who may see payouts (only admin, or also accounts staff)?

---

## 5. Phases (do one at a time, in this order)

| Phase | Goal | Touches existing LMS? |
|---|---|---|
| 0 | **Discovery, no code.** Claude reads the LMS and writes `docs/team-portal/INTEGRATION_PLAN.md`: stack, overlaps, conflicts, where each portal feature fits, the fee module findings, the risks, and a proposed data model with `portal_` tables. You approve. | No |
| 1 | **Foundation.** Staff accounts (reuse the LMS users and login), roles, the `/portal` shell with sidebar, layout and the existing design components. Employees list and profile. | Adds a role or permission only |
| 2 | **Tasks.** Create, assign, reassign, accept, submit, review, revision history, comments, scoring, settings table. | No |
| 3 | **Notifications and attendance.** Reuse the LMS notification system if it exists. Deadline and overdue jobs. First-login attendance. | Maybe reuse |
| 4 | **Work log.** Pre-filled day template, dropdowns, extra entries, submit and lock. | No |
| 5 | **Responsibilities.** Duties, transfer, and the permission mapping (`content`, `audience`). | No |
| 6 | **Analytics.** Employee and organisation views. | No |
| 7 | **Paid tasks and the fee link.** Claim rule, payment status, ledger or payout integration, idempotency, audit. | **Yes, carefully** |
| 8 | **Social media module.** Reel pipeline, month plan, review, audience numbers, daily plans, weekly goals, growth pay. Reuses file storage for thumbnails and Word prompts. | Storage only |
| 9 | **Hardening.** Permission tests, backups, performance, accessibility, phone layout, optional installable app. | No |
| 10 | **Course Planner** (build last). See Part B. | **Yes**, reuses Course, Class, Teacher, Timetable and Fee |

Each phase is shippable on its own. Employees can start using the portal after phase 2 or 4.

---

## 6. Copy-paste prompts

**Phase 0 (start here)**
```
Read CLAUDE.md and docs/team-portal/BRAINOVA_PORTAL_HANDOFF.md. Then open
docs/team-portal/brainova-team-board.html and read the top comment and the Developer Handoff page text in the script
(search for "function handoffView"). Do not write any code yet.

Inspect this LMS and write docs/team-portal/INTEGRATION_PLAN.md covering:
1. Stack, database, migration tool, auth and role system, routing, UI components, notification and file storage.
2. Existing features that overlap with the portal (tasks, attendance, notifications, payroll, roles). For each, say reuse, link or build new.
3. The fee module: how it works, whether it has expenses, payroll or a ledger, and the safest way to record staff payouts.
4. Proposed new tables with the portal_ prefix, and the new routes.
5. Risks and anything that could conflict with the existing system.
6. Questions for me.
Stop after writing the plan.
```

**Phase N (use for phases 1 to 9)**
```
Implement Phase N from docs/team-portal/BRAINOVA_PORTAL_HANDOFF.md, following docs/team-portal/INTEGRATION_PLAN.md
and the standing rules in CLAUDE.md. Use the prototype HTML as the spec for screens, wording and rules; match this LMS's
own components and style instead of copying the prototype's CSS. Each Api method in the prototype becomes a server endpoint
with permission checks. Add migrations (additive and reversible), tests for each business rule, and a CHANGELOG entry.
Do not change existing tables or routes. When finished, list the files changed and the tests run, then stop.
```

**Phase 7 (fee link), add this**
```
Before coding, show me the exact design for recording a paid task payout in the fee module (which table or service, the fields,
how duplicates are prevented, who can see it). Wait for my approval. Never write to student fee records.
```

---

## 6b. Part B: Course Planner (phase 10)

Open the HTML, sign in as the admin (`admin@brainova.demo`, password `demo1234`) and click **Course Planner** in the sidebar. Its own "For developers" tab and `portalSpec().coursePlanner` (Settings, Export for your AI agent) repeat the rules below.

What it does: the admin plans courses for the next 3, 6, 12 months, 2 years or 5 years. Each planned course has a title, category, class, teacher, start date, length in weeks, teaching days, time slot, hours per session, seats, fee per student, fee plan and five setup steps. The window shows what needs attention, setup and delivery progress, a timeline, a course table and teacher load against each teacher's weekly limit.

Stages: stored as idea, planned or created. Derived: **ready** (planned, all five setup steps done, teacher and class chosen), **running** and **completed**.

The create trigger (`POST /course-plans/:id/create`):
- Idempotent. A second call returns the course already created.
- One database transaction that creates, through the LMS's own services: the course, the class group, every timetable session, the teacher link, the fee plan with instalment dates, and the enrolment opening date.
- Sessions: every selected weekday from the start date for weeks x 7 days.
- Rejected when: a setup step is open, teacher or class is missing, the start date is in the past, or the teacher is already booked in the same time slot on a shared weekday in overlapping dates.
- After creation the plan record is locked and keeps the new course id (and the course keeps the plan id).
- Fee plan: full fee, 2 or 3 instalments, or monthly. First payment is due 7 days before the start. Use the existing fee module. Never write fee records by hand.

Avoid conflicts:
- The LMS already has courses, classes, teachers, timetable and fees. Claude must map each planner field to the existing entity and say so in INTEGRATION_PLAN.md before coding.
- Plan data goes in new `courseplan_` tables only. Admin-only access, checked on the server.
- If the LMS has no concept for something (for example enrolment opening date), ask me before adding it.

Phase 10 prompts:

**10a (design, no code)**
```
Read CLAUDE.md, docs/team-portal/BRAINOVA_PORTAL_HANDOFF.md (Part B) and the Course Planner in
docs/team-portal/brainova-team-board.html (sidebar item Course Planner; search the file for "COURSE PLANNER").
Do not write code. Inspect how this LMS creates a course today: the models, services, timetable, class groups,
teacher assignment and fee structure. Write docs/team-portal/COURSE_PLANNER_PLAN.md with: a table mapping each planner
field to an existing LMS entity or field, anything missing, the exact steps the create trigger will run in one
transaction using existing services, the courseplan_ tables needed, how duplicate creation is prevented, and questions for me.
Stop after writing it.
```

**10b (build the planning screens)**
```
Implement the planning screens of the Course Planner (overview, timeline, courses, teachers) following
COURSE_PLANNER_PLAN.md and the prototype. Use this LMS's components and styles. Store plans in courseplan_ tables.
Do NOT implement the create trigger yet; show the Create course button disabled with a tooltip. Add tests and a CHANGELOG entry.
```

**10c (build the trigger)**
```
Implement POST /course-plans/:id/create exactly as described in Part B and COURSE_PLANNER_PLAN.md, calling the LMS's existing
services for course, class group, sessions, teacher link and fee plan inside one transaction. It must be idempotent and
reject the cases listed. Add tests for every rejection and for running it twice. Show me the diff before enabling the button.
```

---

## 7. Business rules Claude must get exactly right

- Claim a paid task only if the employee has no task that is overdue and none in Revision.
- Overdue = status assigned, in progress or revision, with a due date before today. Work submitted for review is not overdue.
- Final score = revision score (by number of revisions, from the editable table, default 5,4,3,2,1,0) + quality score (0 to 5, admin only). Changing the table re-scores completed tasks.
- Submission history is never deleted. Each revision keeps its own version, comments and result.
- Work log: entries cannot overlap; a submitted day is locked; the day template is computed from settings (start, work hours, lunch after N hours), and an entry exists only once the employee picks something.
- Reels: only admin and the content coordinator can create, plan, import and review. Only the person responsible for `audience` (and admin) can log views and followers.
- Admin can transfer any responsibility; the new and previous person are notified; permissions move with it.
- Attendance is the first login of the day.
- Deactivated employees cannot sign in; their history stays.

---

## 8. Things in the prototype that are demo only (replace, do not copy)

- `seedPortal()` demo data and the demo passwords.
- `localStorage` persistence of portal data.
- The mock `Api` object (replace each method with a server call).
- Client-side permission checks (`isAdmin`, `isCoord`, `canContent`, `canLogAudience`): keep them for the interface, **and** enforce the same on the server.
- File handling (only file names are stored in the prototype): use the LMS's real file storage.
- Social board data uses the artifact's shared database; move it to real tables.
- Demo audience numbers ("preview with example data").
