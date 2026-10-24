<?php

namespace App\Repositories\Attendance;

use App\Models\Portal\PortalAttendance;
use App\Models\Staff\Staff;

class StaffAttendanceRepository
{
    // Built off portal_attendances (the auto-recorded "first login of the
    // day" table) rather than a manual mark-attendance table, since no
    // manual staff attendance marking exists yet. Every row here already
    // represents a real recorded login, so there is no "absent" state —
    // this is a filterable login log, not a present/absent matrix.
    private function baseQuery($request)
    {
        $query = PortalAttendance::query()->with(['staff.role']);

        if ($request->role != "") {
            $query->whereHas('staff', fn ($q) => $q->where('role_id', $request->role));
        }
        if ($request->staff != "") {
            $query->where('staff_id', $request->staff);
        }
        if ($request->date_from != "" && $request->date_to != "") {
            $query->whereBetween('date', [$request->date_from, $request->date_to]);
        } elseif ($request->date_from != "") {
            $query->where('date', '>=', $request->date_from);
        } elseif ($request->date_to != "") {
            $query->where('date', '<=', $request->date_to);
        }

        return $query->orderByDesc('date');
    }

    public function search($request)
    {
        return $this->baseQuery($request)->paginate(10)->withQueryString();
    }

    public function searchForExport($request)
    {
        return $this->baseQuery($request)->get()->map(function ($row) {
            return [
                'staff'       => trim(($row->staff->first_name ?? '') . ' ' . ($row->staff->last_name ?? '')),
                'role'        => optional($row->staff->role ?? null)->name ?? '',
                'date'        => optional($row->date)->format('Y-m-d'),
                'first_login' => optional($row->first_login_at)->format('h:i A'),
            ];
        });
    }

    public function staffForRole($roleId)
    {
        $query = Staff::query();
        if ($roleId != "") {
            $query->where('role_id', $roleId);
        }

        return $query->orderBy('first_name')->get();
    }
}
