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

    public function handle()
    {
        $today    = now()->format('Y-m-d');
        $tomorrow = now()->addDay()->format('Y-m-d');

        $pending = PortalTask::with('assignee')
            ->whereIn('status', PortalTask::OVERDUE_STATUSES)
            ->whereIn('due_date', [$today, $tomorrow])
            ->get();

        foreach ($pending as $task) {
            $assigneeUserId = $task->assignee->user_id ?? null;
            if (!$assigneeUserId) {
                continue;
            }

            $url = route('portal-tasks.show', $task->id);

            if (!PortalNotifier::alreadySentToday($assigneeUserId, 'Deadline approaching', $url)) {
                PortalNotifier::send($assigneeUserId, 'Deadline approaching', $task->title, $url);
            }
        }

        $overdue = PortalTask::with('assignee')
            ->whereIn('status', PortalTask::OVERDUE_STATUSES)
            ->where('due_date', '<', $today)
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
