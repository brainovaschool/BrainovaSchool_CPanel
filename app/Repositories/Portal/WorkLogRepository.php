<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalDaySubmission;
use App\Models\Portal\PortalTask;
use App\Models\Portal\PortalWorkLogEntry;
use App\Models\Staff\Staff;
use App\Services\Portal\PortalNotifier;
use App\Traits\ReturnFormatTrait;
use Carbon\Carbon;

/** Work log — handoff spec phase 4. Entries live independently of the
 *  day template (Settings::daySlots()); the template only decides what
 *  the "My Work Log" screen offers to fill in, never what's stored. */
class WorkLogRepository
{
    use ReturnFormatTrait;

    /** A day is locked once it's either explicitly submitted, or its grace
     *  period has run out -- whichever comes first. Keeps someone from
     *  editing a log indefinitely just by never pressing Submit. */
    public function isLocked(int $staffId, string $date): bool
    {
        return $this->isSubmitted($staffId, $date) || $this->gracePeriodExpired($date);
    }

    public function isSubmitted(int $staffId, string $date): bool
    {
        return PortalDaySubmission::where('staff_id', $staffId)->where('date', $date)->exists();
    }

    /** The grace period for a given day's log runs until noon the day
     *  after it, so someone who forgets to submit at the end of the day
     *  can still fix it first thing the next morning -- but it can't be
     *  edited forever. */
    public function gracePeriodExpired(string $date): bool
    {
        return now()->greaterThan(Carbon::parse($date)->addDay()->setTime(12, 0));
    }

    public function entriesForDay(int $staffId, string $date)
    {
        return PortalWorkLogEntry::with('task')->where('staff_id', $staffId)->where('date', $date)->orderBy('start_time')->get();
    }

    public function saveEntry($request, int $staffId): array
    {
        $date = $request->date;
        if ($this->isLocked($staffId, $date)) {
            return $this->responseWithError('This day has already been submitted and is locked.', []);
        }

        $start = $request->start_time;
        $end   = $request->end_time;
        if (strtotime($start) >= strtotime($end)) {
            return $this->responseWithError('Start time must be before end time.', []);
        }

        $taskId = $request->task_id ?: null;
        if ($taskId && !PortalTask::where('id', $taskId)->where('assigned_to', $staffId)->exists()) {
            return $this->responseWithError('You can only log time against your own tasks.', []);
        }
        if (!$taskId && !$request->filled('activity')) {
            return $this->responseWithError('Pick a task or an activity for this slot.', []);
        }

        $overlaps = PortalWorkLogEntry::where('staff_id', $staffId)->where('date', $date)
            ->when($request->id, fn ($q, $id) => $q->where('id', '!=', $id))
            ->where(fn ($q) => $q->where('start_time', '<', $end)->where('end_time', '>', $start))
            ->exists();
        if ($overlaps) {
            return $this->responseWithError('That overlaps another entry already logged for the day.', []);
        }

        $entry = $request->id ? PortalWorkLogEntry::where('id', $request->id)->where('staff_id', $staffId)->first() : new PortalWorkLogEntry();
        if ($request->id && !$entry) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $entry->staff_id   = $staffId;
        $entry->date       = $date;
        $entry->start_time = $start;
        $entry->end_time   = $end;
        $entry->task_id    = $taskId;
        $entry->activity   = $taskId ? null : $request->activity;
        $entry->notes      = $request->notes;
        $entry->is_extra   = (bool) $request->boolean('is_extra');
        $entry->save();

        return $this->responseWithSuccess('Saved.', ['id' => $entry->id]);
    }

    public function deleteEntry(int $id, int $staffId): array
    {
        $entry = PortalWorkLogEntry::where('id', $id)->where('staff_id', $staffId)->first();
        if (!$entry) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($this->isLocked($staffId, $entry->date->format('Y-m-d'))) {
            return $this->responseWithError('This day has already been submitted and is locked.', []);
        }

        $entry->delete();
        return $this->responseWithSuccess('Removed.', []);
    }

    public function submitDay(int $staffId, string $date): array
    {
        if ($this->isSubmitted($staffId, $date)) {
            return $this->responseWithError('This day has already been submitted.', []);
        }

        PortalDaySubmission::create([
            'staff_id'     => $staffId,
            'date'         => $date,
            'submitted_at' => now(),
        ]);

        return $this->responseWithSuccess('Day submitted — it\'s now locked.', []);
    }

    /** Last $days calendar days, newest first — total hours logged and
     *  whether that day was submitted. */
    public function history(int $staffId, int $days = 14): array
    {
        $from = now()->subDays($days - 1)->format('Y-m-d');

        $entriesByDate = PortalWorkLogEntry::where('staff_id', $staffId)->where('date', '>=', $from)->get()
            ->groupBy(fn ($e) => $e->date->format('Y-m-d'));
        $submittedDates = PortalDaySubmission::where('staff_id', $staffId)->where('date', '>=', $from)->pluck('date')
            ->map(fn ($d) => $d->format('Y-m-d'))->flip();

        $rows = [];
        for ($i = 0; $i < $days; $i++) {
            $d = now()->subDays($i)->format('Y-m-d');
            $submitted = $submittedDates->has($d);
            $rows[] = [
                'date'      => $d,
                'hours'     => round(($entriesByDate->get($d) ?? collect())->sum(fn ($e) => $e->hours()), 2),
                'submitted' => $submitted,
                'locked'    => $submitted || $this->gracePeriodExpired($d),
            ];
        }
        return $rows;
    }

    /** One row per staff member with their total hours logged in the
     *  range — the manager's "hours by employee" overview. $seesEveryone
     *  (Super Admin/Admin) bypasses the coordinator scoping entirely;
     *  everyone else only sees the staff they're a coordinator for. */
    public function managerSummary(string $from, string $to, ?int $actingStaffId, bool $seesEveryone)
    {
        $totals = PortalWorkLogEntry::whereBetween('date', [$from, $to])->get()
            ->groupBy('staff_id')
            ->map(fn ($rows) => round($rows->sum(fn ($e) => $e->hours()), 2));

        $staffQuery = Staff::with('role')->orderBy('first_name');
        if (!$seesEveryone) {
            $visibleIds = $actingStaffId
                ? Staff::find($actingStaffId)?->coordinatees->pluck('id')->toArray() ?? []
                : [];
            $staffQuery->whereIn('id', $visibleIds);
        }

        return $staffQuery->get()->map(fn (Staff $staff) => [
            'staff' => $staff,
            'hours' => $totals->get($staff->id, 0),
        ]);
    }

    /** Whether $actingStaffId is allowed to view $targetStaffId's work log --
     *  true for a Super Admin/Admin always, otherwise only if they're one
     *  of that staff member's assigned coordinators. */
    public function canView(?int $actingStaffId, int $targetStaffId, bool $seesEveryone): bool
    {
        if ($seesEveryone) {
            return true;
        }
        if (!$actingStaffId) {
            return false;
        }
        return Staff::find($actingStaffId)?->coordinatees->contains('id', $targetStaffId) ?? false;
    }

    /** One employee's entries in the range, grouped by day — the drill-down. */
    public function employeeRange(int $staffId, string $from, string $to)
    {
        return PortalWorkLogEntry::with('task')->where('staff_id', $staffId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->groupBy(fn ($e) => $e->date->format('Y-m-d'));
    }

    /** Permanently deletes every entry (and its day-submission record)
     *  logged before $beforeDate. Returns the number of entries removed. */
    public function bulkDeleteOlderThan(string $beforeDate): int
    {
        $deleted = PortalWorkLogEntry::where('date', '<', $beforeDate)->delete();
        PortalDaySubmission::where('date', '<', $beforeDate)->delete();
        return $deleted;
    }

    /** A coordinator's observation on one specific entry. Notifies the
     *  staff member it belongs to so they see it without having to go
     *  looking -- same bell/table every other Team Portal notice uses. */
    public function saveFeedback(int $entryId, string $feedback): array
    {
        $entry = PortalWorkLogEntry::with('staff')->find($entryId);
        if (!$entry) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $entry->feedback = $feedback;
        $entry->save();

        if ($entry->staff && $entry->staff->user_id) {
            PortalNotifier::send(
                $entry->staff->user_id,
                'Feedback on your work log',
                "{$entry->start_label}–{$entry->end_label} on " . $entry->date->format('d M Y') . ': ' . $feedback,
                route('portal-work-log.index', ['date' => $entry->date->format('Y-m-d')])
            );
        }

        return $this->responseWithSuccess('Feedback saved.', []);
    }
}
