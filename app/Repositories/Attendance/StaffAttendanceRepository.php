<?php

namespace App\Repositories\Attendance;

use App\Models\LeaveRequest;
use App\Models\Portal\PortalAttendance;
use App\Models\Staff\Staff;
use Carbon\Carbon;

class StaffAttendanceRepository
{
    public const PRESENT = 'P';
    public const ABSENT = 'A';
    public const LEAVE = 'L';
    public const HALF_LEAVE = 'H';

    private function staffQuery($request)
    {
        $query = Staff::query()->with('role')->active();

        if ($request->role != "") {
            $query->where('role_id', $request->role);
        }
        if ($request->staff != "") {
            $query->where('id', $request->staff);
        }

        return $query->orderBy('first_name');
    }

    private function monthBounds($request): array
    {
        $month = $request->month != "" ? $request->month : date('Y-m');
        [$year, $monthNum] = array_map('intval', explode('-', $month));
        $start = Carbon::create($year, $monthNum, 1)->startOfMonth();
        $end = Carbon::create($year, $monthNum, 1)->endOfMonth();

        return [$year, $monthNum, $start, $end];
    }

    /**
     * Present = a portal_attendances row exists that day (they logged in).
     * Leave / Half Leave = an approved leave request covers that day, for
     * the half_day flag on it. Absent = neither. No staff holiday/weekend
     * calendar exists yet, so every day in the month is a candidate - a
     * weekend with nobody logged in and no leave on file shows as Absent,
     * same as any other day without a record.
     */
    public function searchGrid($request)
    {
        [$year, $monthNum, $start, $end] = $this->monthBounds($request);

        $staffPaginated = $this->staffQuery($request)->paginate(10)->withQueryString();
        $staffIds = $staffPaginated->pluck('id');
        $userIds = $staffPaginated->pluck('user_id')->filter();

        $logins = PortalAttendance::whereIn('staff_id', $staffIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->groupBy(fn ($row) => $row->staff_id . '_' . $row->date->format('Y-m-d'));

        $leaves = LeaveRequest::whereIn('user_id', $userIds)
            ->where('approval_status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get();

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->toDateString();
        }

        $statusGrid = [];
        $summary = [];
        foreach ($staffPaginated as $staff) {
            $staffLeaves = $leaves->where('user_id', $staff->user_id);
            $counts = [self::PRESENT => 0, self::ABSENT => 0, self::LEAVE => 0, self::HALF_LEAVE => 0];

            foreach ($days as $date) {
                $status = $this->resolveStatus($staff->id, $date, $logins, $staffLeaves);
                $statusGrid[$staff->id][$date] = $status;
                $counts[$status]++;
            }

            $summary[$staff->id] = $counts;
        }

        return [
            'staffList' => $staffPaginated,
            'days' => $days,
            'statusGrid' => $statusGrid,
            'summary' => $summary,
        ];
    }

    private function resolveStatus($staffId, $date, $logins, $staffLeaves)
    {
        if ($logins->has($staffId . '_' . $date)) {
            return self::PRESENT;
        }

        $leave = $staffLeaves->first(fn ($lr) => $date >= $lr->start_date && $date <= $lr->end_date);
        if ($leave) {
            return $leave->is_half_day ? self::HALF_LEAVE : self::LEAVE;
        }

        return self::ABSENT;
    }

    /** Flat, unpaginated rows for the Excel export - one row per staff per
     *  day in the month, with status and (when present) the login time. */
    public function searchForExport($request)
    {
        [$year, $monthNum, $start, $end] = $this->monthBounds($request);

        $staffList = $this->staffQuery($request)->get();
        $staffIds = $staffList->pluck('id');
        $userIds = $staffList->pluck('user_id')->filter();

        $logins = PortalAttendance::whereIn('staff_id', $staffIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($row) => $row->staff_id . '_' . $row->date->format('Y-m-d'));

        $leaves = LeaveRequest::whereIn('user_id', $userIds)
            ->where('approval_status', 'approved')
            ->where('start_date', '<=', $end->toDateString())
            ->where('end_date', '>=', $start->toDateString())
            ->get();

        $days = [];
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            $days[] = $d->toDateString();
        }

        $labels = [
            self::PRESENT => 'Present',
            self::ABSENT => 'Absent',
            self::LEAVE => 'Leave',
            self::HALF_LEAVE => 'Half Leave',
        ];

        $rows = [];
        foreach ($staffList as $staff) {
            $staffLeaves = $leaves->where('user_id', $staff->user_id);
            foreach ($days as $date) {
                // $logins is keyBy'd here (groupBy'd in searchGrid instead),
                // but ->has() works identically either way - resolveStatus()
                // only ever calls ->has() on it.
                $status = $this->resolveStatus($staff->id, $date, $logins, $staffLeaves);
                $login = $logins->get($staff->id . '_' . $date);
                $rows[] = [
                    'staff' => trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? '')),
                    'role' => optional($staff->role)->name ?? '',
                    'date' => $date,
                    'status' => $labels[$status],
                    'first_login' => $login ? $login->first_login_at->format('h:i A') : '',
                ];
            }
        }

        return $rows;
    }

    /** Staff list for the filter dropdown itself (not the grid) - scoped
     *  by role only, never by a specific staff id. */
    public function staffForRole($roleId)
    {
        $query = Staff::query()->active();
        if ($roleId != "") {
            $query->where('role_id', $roleId);
        }

        return $query->orderBy('first_name')->get();
    }
}
