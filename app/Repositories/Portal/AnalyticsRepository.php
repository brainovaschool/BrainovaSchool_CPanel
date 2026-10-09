<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalAttendance;
use App\Models\Portal\PortalTask;
use App\Models\Portal\PortalWorkLogEntry;
use App\Models\Staff\Staff;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

/** Analytics — handoff spec phase 6. Built as plain tables and a simple
 *  CSS bar trend, matching every other report screen in this LMS, rather
 *  than wiring in a chart library the backend hasn't established a
 *  pattern for yet. */
class AnalyticsRepository
{
    public function orgSummary(string $from, string $to, array $filters = []): array
    {
        $tasks = $this->filteredTasks($from, $to, $filters)->get();
        $completed = $tasks->where('status', PortalTask::COMPLETED);

        $overdue = PortalTask::whereIn('status', PortalTask::OVERDUE_STATUSES)
            ->where('due_date', '<', now()->format('Y-m-d'))
            ->when($filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->count();

        $hours = PortalWorkLogEntry::whereBetween('date', [$from, $to])
            ->when($filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('staff_id', $v))
            ->get()->sum(fn ($e) => $e->hours());

        return [
            'assigned'  => $tasks->count(),
            'completed' => $completed->count(),
            'overdue'   => $overdue,
            'avg_score' => $completed->count() ? round($completed->avg('final_score'), 1) : null,
            'hours'     => round($hours, 2),
        ];
    }

    /** One row per employee — the admin's ranking/comparison table. */
    public function byEmployee(string $from, string $to, array $filters = [])
    {
        $staffQuery = Staff::with('role')->orderBy('first_name');
        if (!empty($filters['assigned_to'])) {
            $staffQuery->where('id', $filters['assigned_to']);
        }

        return $staffQuery->get()->map(function (Staff $staff) use ($from, $to, $filters) {
            $tasks = $this->filteredTasks($from, $to, $filters)->where('assigned_to', $staff->id)->get();
            $completed = $tasks->where('status', PortalTask::COMPLETED);
            $onTime = $completed->filter(fn ($t) => $t->completed_at && $t->due_date && $t->completed_at->lte($t->due_date->copy()->endOfDay()));

            $hours = PortalWorkLogEntry::where('staff_id', $staff->id)->whereBetween('date', [$from, $to])->get()->sum(fn ($e) => $e->hours());
            $attendanceDays = PortalAttendance::where('staff_id', $staff->id)->whereBetween('date', [$from, $to])->count();

            return [
                'staff'          => $staff,
                'assigned'       => $tasks->count(),
                'completed'      => $completed->count(),
                'avg_score'      => $completed->count() ? round($completed->avg('final_score'), 1) : null,
                'on_time_rate'   => $completed->count() ? round($onTime->count() / $completed->count() * 100) : null,
                'hours'          => round($hours, 2),
                'attendance_days' => $attendanceDays,
            ];
        })->sortByDesc(fn ($row) => $row['avg_score'] ?? -1)->values();
    }

    /** Completed-tasks-per-day across the range, for the bar trend. */
    public function trend(string $from, string $to, ?int $staffId = null): array
    {
        $query = PortalTask::where('status', PortalTask::COMPLETED)
            ->whereBetween('completed_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);
        if ($staffId) {
            $query->where('assigned_to', $staffId);
        }

        $byDate = $query->get()->groupBy(fn ($t) => $t->completed_at->format('Y-m-d'))->map->count();

        $out = [];
        foreach (CarbonPeriod::create($from, $to) as $date) {
            $d = $date->format('Y-m-d');
            $out[] = ['date' => $d, 'count' => $byDate->get($d, 0)];
        }
        return $out;
    }

    public function myPerformance(int $staffId, string $from, string $to): array
    {
        $rows = $this->byEmployee($from, $to, ['assigned_to' => $staffId]);
        return $rows->first() ?? [];
    }

    public function myTaskHistory(int $staffId)
    {
        return PortalTask::where('assigned_to', $staffId)->where('status', PortalTask::COMPLETED)
            ->orderByDesc('completed_at')->get();
    }

    private function filteredTasks(string $from, string $to, array $filters)
    {
        return PortalTask::whereBetween('assigned_date', [$from, $to])
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->where('priority', $v))
            ->when($filters['assigned_to'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v));
    }
}
