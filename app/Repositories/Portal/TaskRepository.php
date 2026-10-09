<?php

namespace App\Repositories\Portal;

use App\Enums\Settings;
use App\Models\Accounts\AccountHead;
use App\Models\Accounts\Expense;
use App\Models\Portal\PortalPayout;
use App\Models\Portal\PortalSetting;
use App\Models\Portal\PortalTask;
use App\Models\Portal\PortalTaskComment;
use App\Models\Portal\PortalTaskSubmission;
use App\Models\Staff\Staff;
use App\Services\Portal\PortalNotifier;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

/** Team Portal task board — handoff spec phase 2. Workflow (spec section 5):
 *  Assigned -> In progress -> Submitted -> Under review -> (Revision
 *  required, loops back to In progress) or Completed. "Open" (unclaimed
 *  paid task) and the claim/payment actions are phase 7, not built here. */
class TaskRepository
{
    use ReturnFormatTrait, CommonHelperTrait;

    public function forManager(array $filters)
    {
        return PortalTask::with(['assignee', 'assignedBy'])
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where('title', 'like', "%{$v}%"))
            ->orderByDesc('id')
            ->paginate(Settings::PAGINATE);
    }

    /** Overdue first, matching the spec's "Employee: grouped lists with
     *  overdue first" — everything else is simplest left as one list
     *  underneath, ordered by due date. */
    public function forEmployee(int $staffId)
    {
        $tasks = PortalTask::with(['assignedBy'])
            ->where('assigned_to', $staffId)
            ->orderBy('due_date')
            ->get();

        return $tasks->sortByDesc(fn ($t) => $t->is_overdue ? 1 : 0)->values();
    }

    public function show(int $id): ?PortalTask
    {
        return PortalTask::with(['assignee', 'assignedBy', 'refUpload', 'submissions.submittedBy', 'submissions.reviewedBy', 'submissions.upload', 'comments.user'])->find($id);
    }

    public function store($request, int $assignedByUserId): array
    {
        try {
            $refUploadId = null;
            if ($request->hasFile('ref_file')) {
                $refUploadId = $this->UploadImageCreate($request->file('ref_file'), 'uploads/portal-tasks');
            }

            $isPaid = (bool) $request->boolean('paid');
            // A paid task left with no one picked in "Assign to" stays Open
            // for anyone to claim — a non-paid task always needs an assignee
            // (enforced in the controller's validation).
            $isOpen = $isPaid && !$request->filled('assigned_to');

            $task = PortalTask::create([
                'title'          => $request->title,
                'description'    => $request->description,
                'category'       => $request->category,
                'priority'       => $request->priority ?: 'Medium',
                'urgent'         => (bool) $request->boolean('urgent'),
                'assigned_to'    => $isOpen ? null : $request->assigned_to,
                'assigned_by'    => $assignedByUserId,
                'assigned_date'  => now()->format('Y-m-d'),
                'due_date'       => $request->due_date,
                'est_hours'      => $request->est_hours,
                'output'         => $request->output,
                'format'         => $request->format,
                'refs'           => $request->refs,
                'ref_upload_id'  => $refUploadId,
                'drive_link'     => $request->drive_link,
                'notes'          => $request->notes,
                'paid'           => $isPaid,
                'amount'         => $isPaid ? $request->amount : null,
                'pay_status'     => $isPaid ? 'unpaid' : null,
                'status'         => $isOpen ? PortalTask::OPEN : PortalTask::ASSIGNED,
            ]);

            if ($isOpen) {
                // "Paid task available -> All active employees"
                Staff::active()->whereNotNull('user_id')->get()->each(function (Staff $staff) use ($task) {
                    PortalNotifier::send($staff->user_id, 'Paid task available', $task->title, route('portal-tasks.show', $task->id));
                });
            } elseif ($task->assignee && $task->assignee->user_id) {
                PortalNotifier::send(
                    $task->assignee->user_id,
                    $task->urgent ? 'Urgent task assigned' : 'New task assigned',
                    $task->title,
                    route('portal-tasks.show', $task->id)
                );
            }

            return $this->responseWithSuccess($isOpen ? 'Open paid task created.' : 'Task created and assigned.', ['id' => $task->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** The tasks (per business rule 1) that would block this employee from
     *  claiming a paid task right now — none overdue, none in revision.
     *  Returned (not just a bool) so the UI can name them. */
    public function claimBlockers(int $staffId)
    {
        return PortalTask::where('assigned_to', $staffId)
            ->where(function ($q) {
                $q->where('status', PortalTask::REVISION)
                    ->orWhere(function ($q2) {
                        $q2->whereIn('status', PortalTask::OVERDUE_STATUSES)->where('due_date', '<', now()->format('Y-m-d'));
                    });
            })
            ->get();
    }

    /** "Open (paid) -> Claim -> Employee -> In progress" — claiming skips
     *  the separate accept step entirely, per the workflow table. */
    public function claim(int $id, int $staffId): array
    {
        $task = PortalTask::find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($task->status !== PortalTask::OPEN || !$task->paid) {
            return $this->responseWithError('This task is no longer available to claim.', []);
        }

        $blockers = $this->claimBlockers($staffId);
        if ($blockers->isNotEmpty()) {
            return $this->responseWithError('You can\'t claim a paid task while you have an overdue task or one in revision: ' . $blockers->pluck('title')->implode(', '), []);
        }

        $task->assigned_to = $staffId;
        $task->status      = PortalTask::IN_PROGRESS;
        $task->claimed_at  = now();
        $task->save();

        if ($task->assigned_by) {
            PortalNotifier::send($task->assigned_by, 'Paid task claimed', $task->title, route('portal-tasks.show', $task->id));
        }

        return $this->responseWithSuccess('Claimed — it\'s now in progress.', []);
    }

    /** Creates the real Expense row (never a student fee entry) and the
     *  portal's own audit record in one transaction. The unique index on
     *  portal_payouts.task_id, plus this status/pay_status guard, is what
     *  makes marking paid twice impossible. */
    public function markPaid(int $id, int $actingUserId): array
    {
        $task = PortalTask::with('assignee')->find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if (!$task->paid || $task->status !== PortalTask::COMPLETED || $task->pay_status !== 'due') {
            return $this->responseWithError('This task isn\'t waiting on a payment.', []);
        }
        if (PortalPayout::where('task_id', $task->id)->exists()) {
            return $this->responseWithError('This has already been marked paid.', []);
        }
        if (!$task->assignee) {
            return $this->responseWithError('This task has no assignee to pay.', []);
        }

        $head = AccountHead::where('name', 'Staff Task Payouts')->where('type', 2)->first();
        if (!$head) {
            return $this->responseWithError('The "Staff Task Payouts" expense category hasn\'t been set up yet — visit the one-off seed link first.', []);
        }

        DB::transaction(function () use ($task, $actingUserId, $head) {
            $employeeName = trim($task->assignee->first_name . ' ' . $task->assignee->last_name);

            $expense = new Expense();
            $expense->session_id   = setting('session');
            $expense->name         = 'Team Portal payout — ' . $task->title;
            $expense->expense_head = $head->id;
            $expense->date         = now()->format('Y-m-d');
            $expense->amount       = $task->amount;
            $expense->description  = "Paid task #{$task->id} \"{$task->title}\" — {$employeeName}";
            $expense->save();

            PortalPayout::create([
                'task_id'         => $task->id,
                'staff_id'        => $task->assigned_to,
                'amount'          => $task->amount,
                'marked_paid_by'  => $actingUserId,
                'marked_paid_at'  => now(),
                'expense_id'      => $expense->id,
            ]);

            $task->pay_status = 'paid';
            $task->save();
        });

        if ($task->assignee->user_id) {
            PortalNotifier::send($task->assignee->user_id, 'Payment marked as paid', $task->title, route('portal-tasks.show', $task->id));
        }

        return $this->responseWithSuccess('Marked paid — recorded as an expense.', []);
    }

    public function reassign(int $id, int $newStaffId): array
    {
        $task = PortalTask::find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($task->status === PortalTask::COMPLETED) {
            return $this->responseWithError('This task is already completed — it can\'t be reassigned.', []);
        }

        $task->assigned_to = $newStaffId;
        $task->save();

        return $this->responseWithSuccess('Task reassigned.', []);
    }

    /** Only the assignee may accept their own task. */
    public function accept(int $id, int $staffId): array
    {
        $task = PortalTask::find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ((int) $task->assigned_to !== $staffId) {
            return $this->responseWithError("This isn't your task.", []);
        }
        if ($task->status !== PortalTask::ASSIGNED) {
            return $this->responseWithError('This task has already been started.', []);
        }

        $task->status = PortalTask::IN_PROGRESS;
        $task->save();

        return $this->responseWithSuccess('Task accepted — it\'s now in progress.', []);
    }

    /** Only the assignee, only while it's theirs to work on (in progress,
     *  or kicked back for revision). Adds a new, permanent submission row —
     *  nothing already in the history is ever touched. */
    public function submitWork(int $id, int $staffId, $request): array
    {
        $task = PortalTask::find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ((int) $task->assigned_to !== $staffId) {
            return $this->responseWithError("This isn't your task.", []);
        }
        if (!in_array($task->status, [PortalTask::IN_PROGRESS, PortalTask::REVISION], true)) {
            return $this->responseWithError('This task isn\'t ready to be submitted right now.', []);
        }

        try {
            $uploadId = null;
            if ($request->hasFile('file')) {
                $uploadId = $this->UploadImageCreate($request->file('file'), 'uploads/portal-task-submissions');
            }

            DB::transaction(function () use ($task, $staffId, $request, $uploadId) {
                $n = $task->submissions()->count() + 1;
                PortalTaskSubmission::create([
                    'task_id'      => $task->id,
                    'n'            => $n,
                    'submitted_by' => auth()->id(),
                    'link'         => $request->link,
                    'upload_id'    => $uploadId,
                    'comment'      => $request->comment,
                ]);

                $task->status = PortalTask::SUBMITTED;
                $task->save();
            });

            PortalNotifier::send($task->assigned_by, 'Work submitted', $task->title, route('portal-tasks.show', $task->id));

            return $this->responseWithSuccess('Work submitted for review.', []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function startReview(int $id): array
    {
        $task = PortalTask::find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($task->status !== PortalTask::SUBMITTED) {
            return $this->responseWithError('Only a freshly submitted task can start review.', []);
        }

        $task->status = PortalTask::UNDER_REVIEW;
        $task->save();

        return $this->responseWithSuccess('Marked as under review.', []);
    }

    /** Marks the latest submission as sent back for revision — that row,
     *  and every row before it, stays exactly as submitted forever. */
    public function requestRevision(int $id, int $reviewerUserId, string $comment): array
    {
        $task = PortalTask::with('submissions')->find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if (!in_array($task->status, [PortalTask::SUBMITTED, PortalTask::UNDER_REVIEW], true)) {
            return $this->responseWithError('This task isn\'t waiting on a review decision.', []);
        }

        $latest = $task->submissions->last();
        if (!$latest) {
            return $this->responseWithError('No submission to review yet.', []);
        }

        DB::transaction(function () use ($task, $latest, $reviewerUserId, $comment) {
            $latest->admin_comment = $comment;
            $latest->result        = PortalTaskSubmission::REVISION;
            $latest->reviewed_by   = $reviewerUserId;
            $latest->reviewed_at   = now();
            $latest->save();

            $task->status = PortalTask::REVISION;
            $task->save();
        });

        if ($task->assignee && $task->assignee->user_id) {
            PortalNotifier::send($task->assignee->user_id, 'Revision requested', $task->title, route('portal-tasks.show', $task->id));
        }

        return $this->responseWithSuccess('Sent back for revision.', []);
    }

    /** Approves and completes in one step, computing both score halves —
     *  revision score from the (immutable) submission history against the
     *  current scoring table, quality score as entered right now. */
    public function approve(int $id, int $reviewerUserId, int $qualityScore, ?string $comment = null): array
    {
        if ($qualityScore < 0 || $qualityScore > 5) {
            return $this->responseWithError('Quality score must be between 0 and 5.', []);
        }

        $task = PortalTask::with('submissions')->find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if (!in_array($task->status, [PortalTask::SUBMITTED, PortalTask::UNDER_REVIEW], true)) {
            return $this->responseWithError('This task isn\'t waiting on a review decision.', []);
        }

        $latest = $task->submissions->last();

        DB::transaction(function () use ($task, $latest, $reviewerUserId, $qualityScore, $comment) {
            if ($latest) {
                $latest->admin_comment = $comment;
                $latest->result        = PortalTaskSubmission::APPROVED;
                $latest->reviewed_by   = $reviewerUserId;
                $latest->reviewed_at   = now();
                $latest->save();
            }

            $revisionScore = PortalSetting::current()->scoreForRevisionCount($task->revisionCount());

            $task->quality_score  = $qualityScore;
            $task->revision_score = $revisionScore;
            $task->final_score    = $revisionScore + $qualityScore;
            $task->status         = PortalTask::COMPLETED;
            $task->completed_at   = now();
            if ($task->paid) {
                $task->pay_status = 'due';
            }
            $task->save();
        });

        if ($task->assignee && $task->assignee->user_id) {
            PortalNotifier::send($task->assignee->user_id, 'Submission approved', $task->title, route('portal-tasks.show', $task->id));
        }
        if ($task->paid && $task->assigned_by) {
            PortalNotifier::send($task->assigned_by, 'Paid task completed — payment due', $task->title, route('portal-tasks.show', $task->id));
        }

        return $this->responseWithSuccess('Approved and completed.', []);
    }

    /** $isManager: a manager may comment on any task; an employee only on
     *  their own — enforced by the caller before this runs. */
    public function addComment(int $id, int $userId, string $body): array
    {
        $task = PortalTask::find($id);
        if (!$task) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        PortalTaskComment::create([
            'task_id' => $task->id,
            'user_id' => $userId,
            'body'    => $body,
        ]);

        // "The other party" — whichever side of the conversation didn't
        // just post this comment. Simplest faithful reading when more than
        // one manager can exist (the spec's own model has exactly one).
        $assigneeUserId = $task->assignee->user_id ?? null;
        $otherPartyId = $userId === (int) $task->assigned_by ? $assigneeUserId : (int) $task->assigned_by;
        if ($otherPartyId && $otherPartyId !== $userId) {
            PortalNotifier::send($otherPartyId, 'New comment on a task', $task->title, route('portal-tasks.show', $task->id));
        }

        return $this->responseWithSuccess('Comment added.', []);
    }

    public function updateSettings($request): array
    {
        $scores = array_values(array_map('intval', (array) $request->revision_scores));
        $categories = array_values(array_filter(array_map('trim', (array) $request->categories)));

        if (empty($scores)) {
            return $this->responseWithError('The revision score table can\'t be empty.', []);
        }

        $activities = array_values(array_filter(array_map('trim', (array) $request->activities)));

        $settings = PortalSetting::current();
        $settings->revision_scores   = $scores;
        $settings->categories        = $categories ?: PortalSetting::DEFAULT_CATEGORIES;
        $settings->activities        = $activities ?: PortalSetting::DEFAULT_ACTIVITIES;
        $settings->work_day_hours    = $request->work_day_hours ?: 8;
        $settings->day_start         = $request->day_start ?: '09:00';
        $settings->lunch_after_hours = $request->lunch_after_hours ?: 4;
        $settings->save();

        $this->rescoreCompletedTasks($settings);

        return $this->responseWithSuccess('Settings saved — completed tasks have been re-scored.', []);
    }

    /** "Changing the table re-scores completed tasks" (spec rule 5) — the
     *  quality half never changes, only the revision half, recomputed from
     *  each task's own (untouched) submission history. */
    private function rescoreCompletedTasks(PortalSetting $settings): void
    {
        PortalTask::where('status', PortalTask::COMPLETED)->whereNotNull('quality_score')->get()->each(function (PortalTask $task) use ($settings) {
            $revisionScore = $settings->scoreForRevisionCount($task->revisionCount());
            $task->revision_score = $revisionScore;
            $task->final_score    = $revisionScore + (int) $task->quality_score;
            $task->save();
        });
    }
}
