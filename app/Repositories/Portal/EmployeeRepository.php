<?php

namespace App\Repositories\Portal;

use App\Enums\Settings;
use App\Models\Role;
use App\Models\Staff\Staff;

/** Team Portal "Employees" directory — reads the existing Staff table
 *  directly (every Teacher/Accounting/HR/any admin-created role ends up
 *  there via the normal Staff -> Add Staff screen). No new employee table:
 *  the portal's own identity for a person IS the LMS's Staff/User record. */
class EmployeeRepository
{
    public function index(?int $roleId, ?string $search)
    {
        return Staff::with(['role', 'department', 'designation', 'upload'])
            ->when($roleId, fn ($q) => $q->where('role_id', $roleId))
            ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                $q2->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('first_name')
            ->paginate(Settings::PAGINATE);
    }

    public function show(int $id): ?Staff
    {
        return Staff::with(['role', 'department', 'designation', 'upload', 'user'])->find($id);
    }

    public function roleOptions()
    {
        $roleIds = Staff::select('role_id')->distinct()->pluck('role_id');
        return Role::whereIn('id', $roleIds)->orderBy('name')->get();
    }
}
