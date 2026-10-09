# Brainova Team Portal — Integration Plan (Phase 0: Discovery)

Status: **discovery only, no code written**. Per the handoff doc's own rule, this stops here for approval before Phase 1 begins.

## 1. Stack

- Laravel 12, PHP ^8.2.
- Multi-tenant via `stancl/tenancy` ^3.7 — every school is a separate tenant; tenant-specific tables live in `database/migrations/tenant/`, central ones in `database/migrations/`. Any new Team Portal table is a **tenant** migration (one copy per school), same as every other feature in this app.
- MySQL, Blade templates, Bootstrap + jQuery (no SPA framework, no build step beyond the existing asset pipeline).
- Auth/roles/permissions is a **custom** system (not a package): `roles`, `permissions` tables, `PermissionCheck` middleware, `hasPermission()` helper, plus a per-user permission *snapshot* (`authEffectivePermissions()`) that intersects role permissions with whatever was captured the last time someone used the "Change Permission" screen on that user.
- File uploads go through one shared mechanism: `Upload` model + `CommonHelperTrait::UploadImageCreate($file, $path)`.
- In-app notifications already exist: `system_notifications` table, `SystemNotification` model, bell icon wired globally via a view composer in `AppServiceProvider`, and a template/event system (`NotificationSetting` + `SendNotificationTrait::make_notification()`).
- A cron-driven scheduler already exists: `app/Console/Kernel.php` registers `Schedule::command(...)->everyMinute()` jobs. `AttendaceNotificationCron` is a working precedent for "check a time/condition every minute and fire a notification."

## 2. Existing features that overlap with the Team Portal spec

| Team Portal needs | Already exists as |
|---|---|
| Staff accounts, login, roles | `User`, `Staff`, `Role` models, roles 1–7 (Super Admin, Admin, Staff, Accounting, Teacher, Student, Guardian) |
| Permission-gated screens per role | `PermissionCheck` middleware + `hasPermission()`. **Known fragility**: role-edit screens resubmit the *whole* permission list and can silently drop a grant; per-user snapshots go stale independently of the role. This session's fix pattern (a `PERMISSION_GROUPS` entry + a "non-strippable" + self-healing re-assert on every `/db/migrate` run) must be reused for every new Team Portal permission, not just the role grant. |
| Task-assigned / overdue notifications | `system_notifications` + `make_notification()` — reusable by adding new `event` keys, no new table needed |
| "Check every day/minute for X" | Laravel's `Schedule` in `Kernel.php`, precedent command `AttendaceNotificationCron` |
| Attachments / evidence screenshots | `Upload` model + `UploadImageCreate()` (same mechanism just used for the fee-payment-proof uploads) |
| Admin layout, cards, buttons, badges | `backend/master.blade.php`, sidebar partial, and the Aurora Glass design system (`aurora-glass.css`) — new screens extend the same master, same `.ot-card` / `.ot-btn-*` classes |
| Staff daily attendance / check-in | **Does not exist.** No `StaffAttendance` model, no staff-side route, nothing in `Staff/*Controller`. Would be new. |
| Payroll / salary payout | **Does not exist beyond a raw column.** `staff.basic_salary` is just a display field, never computed or paid against. No `Payroll`/`Salary` model or route anywhere. |
| A generic ledger line for a staff payout | `app/Models/Accounts/Expense.php` (table `expenses`) exists and is structurally the right home for a "paid task payout" line — but it has no `staff_id`/payee column today, and per the handoff's own rule this is Phase 7, gated on a separate approved design, and must never touch the fee tables. |
| Task/project tracking of any kind | **Does not exist.** No `Task`/`Project` model anywhere in the codebase — the entire task board, submissions, and review workflow in the spec is new. |

## 3. The fee module (for context ahead of the later, gated Phase 7)

Just deeply reworked this session, so stated precisely: `FeesGroup → FeesType → FeesMaster → FeesAssign → FeesAssignChildren → FeesCollect`, with `FeesCollect.fees_collect_by` a **hard FK to `users.id`**, never `staff.id`. A separate `FeesPaymentProof` table (just added) handles manual/offline payment evidence with an approval queue, and a separate `Expense`/`Income`/`AccountHead` set handles the school's accounting ledger. **None of this should be touched by the Team Portal** except, much later and only on explicit approval, a new column or small join table linking a `portal_` payout to an `Expense` row — never a write into any `fees_*` table, exactly as the handoff doc requires.

## 4. Proposed new tables (all `portal_`-prefixed, additive only, tenant migrations)

Early phases (task board core):
- `portal_tasks` — title, description, created_by (users.id), status, priority, due_date, timestamps
- `portal_task_assignees` — task_id, user_id (pivot, supports multiple assignees)
- `portal_submissions` — task_id, submitted_by, upload_id (reuses `Upload`), note, status (pending/approved/rejected), reviewed_by, reviewed_at
- `portal_task_comments` — task_id, user_id, body, created_at (activity/comment trail)

Notifications: reuse `system_notifications` with new `event` keys (`portal_task_assigned`, `portal_task_due_soon`, `portal_submission_reviewed`, etc.) rather than a new table — matches question 3 below.

Later, gated phases:
- Phase 7 (payouts): no table proposed yet — the handoff doc requires showing an exact design for approval first, separately, before any migration is written.
- Phase 10 (Course Planner, **not starting without explicit go-ahead**): `courseplan_plans`, `courseplan_checks`, and supporting lookup tables mirroring the prototype's `CATS`/`CLASSES`/`SLOTS` structures — deferred entirely for now.

## 5. Risks

1. **Naming collision**: the Team Portal spec's "Content Coordinator" is described in the handoff doc as a *responsibility tag on existing staff*, not a new account type. This LMS already has an actual **Coordinator role** (built this session, for Class Content review). These are two different things with a confusingly similar name — needs explicit confirmation before any screen uses the word "Coordinator" so the two don't get conflated in permissions or in the UI.
2. **Permission self-healing must be built in from day one.** This session hit real bugs (role-edit screen silently dropping grants, stale per-user snapshots) that only surfaced after the fact. Every new Team Portal permission should follow the established `PERMISSION_GROUPS` + non-strippable + self-healing pattern from the first migration, not retrofitted later.
3. **Notification linkage is free-text.** `system_notifications.url` is a plain string, not a polymorphic reference — fine for deep-linking to a task, but means there's no structured "this notification is about task #42" query; acceptable for the spec's needs but worth knowing up front.
4. **Money is the highest-risk area.** Per the handoff's own rule, Phase 7 payouts must never write to `fees_*` tables and must get a separate approved design before any code — flagging this now so it isn't rushed later given how much fee-system work just happened in this same codebase.

## 6. Questions for the user

1. Is "Content Coordinator" in the Team Portal spec meant as a tag/filter on existing staff (as the handoff doc states), separate from the Class Content **Coordinator role** already built this session? Confirming to avoid naming confusion in permissions and UI.
2. Which existing roles should see the new Team Portal screens — just Admin/Super Admin as managers with all Staff as task recipients, or does this need a new dedicated role (e.g. "HR" / "Portal Manager")?
3. OK to reuse the existing `system_notifications` bell/table for task-assigned, due-soon, and review-decision notifications (adding new event keys), or do you want a visually separate "Portal" notification feed?
4. Confirming the phase order from your handoff doc is still what you want to start with (Phase 1 first), and that Phase 10 (Course Planner) stays off-limits until you explicitly say go.
