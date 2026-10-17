<?php

namespace App\Console\Commands\Portal;

use App\Models\Portal\PortalTask;
use App\Services\Portal\PortalNotifier;
use Illuminate\Console\Command;

/** Handoff spec business rule 8: "Overdue and deadline notices come from
 *  a scheduled job." Runs every minute (same pattern as the existing
 *  attendance:cron) but PortalNotifier::alreadySentToday() means each
 *  task only ever actually sends one deadline notice and one overdue
 *  notice per calendar day, however many times this fires. */
class TaskDeadlineCron extends Command
{
    protected $signature = 'portal:task-deadlines';
    protected $description = 'Notify on tasks due today/tomorrow and tasks overdue (Team Portal)';

    /** How many days out the daily countdown starts (inclusive) — a task
     *  due in 3 days gets its first reminder then, one a day, down to
     *  "due today". */
    private const REMINDER_WINDOW_DAYS = 3;

    public function handle()
    {
        $today = now()->startOfDay();

        $pending = PortalTask::with('assignee')
            ->whereIn('status', PortalTask::OVERDUE_STATUSES)
            ->whereBetween('due_date', [
                $today->format('Y-m-d'),
                $today->copy()->addDays(self::REMINDER_WINDOW_DAYS)->format('Y-m-d'),
            ])
            ->get();

        foreach ($pending as $task) {
            $assigneeUserId = $task->assignee->user_id ?? null;
            if (!$assigneeUserId) {
                continue;
            }

            // Absolute, not signed — Carbon's non-absolute diffInDays sign
            // convention is easy to get backwards, and the query above
            // already guarantees due_date >= today, so there's nothing to
            // disambiguate here.
            $daysLeft = $today->diffInDays($task->due_date->copy()->startOfDay());
            $title = match (true) {
                $daysLeft <= 0 => 'Due today',
                $daysLeft === 1 => 'Due tomorrow',
                default => "Due in {$daysLeft} days",
            };

            $url = route('portal-tasks.show', $task->id);

            // $title itself carries the day count, so each day's distinct
            // countdown message naturally gets its own one-time send —
            // this still never repeats the same title twice in one day.
            if (!PortalNotifier::alreadySentToday($assigneeUserId, $title, $url)) {
                PortalNotifier::send($assigneeUserId, $title, $task->title, $url);
            }
        }

        $overdue = PortalTask::with('assignee')
            ->whereIn('status', PortalTask::OVERDUE_STATUSES)
            ->where('due_date', '<', $today->format('Y-m-d'))
            ->get();

        foreach ($overdue as $task) {
            $url = route('portal-tasks.show', $task->id);

            $assigneeUserId = $task->assignee->user_id ?? null;
            if ($assigneeUserId && !PortalNotifier::alreadySentToday($assigneeUserId, 'Task overdue', $url)) {
                PortalNotifier::send($assigneeUserId, 'Task overdue', $task->title, $url);
            }

            if ($task->assigned_by && !PortalNotifier::alreadySentToday($task->assigned_by, 'Task overdue', $url)) {
                PortalNotifier::send($task->assigned_by, 'Task overdue', $task->title, $url);
            }
        }

        return Command::SUCCESS;
    }
}
