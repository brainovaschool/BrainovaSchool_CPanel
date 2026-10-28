<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Staff\Staff;
use App\Repositories\Portal\TaskRepository;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Thin JSON wrapper around the same TaskRepository the website's Team
 * Portal uses (app/Http/Controllers/Portal/TaskController.php) -- no new
 * business logic, so the mobile app and the website always agree on what
 * "my tasks" means and how a task gets created.
 */
class PortalTaskController extends Controller
{
    use ReturnFormatTrait;

    private $repo;

    public function __construct(TaskRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    public function myTasks()
    {
        try {
            $staffId = $this->actingStaffId();
            if ($staffId === null) {
                return $this->responseWithError('No staff record is linked to your account.');
            }

            return $this->responseWithSuccess(___('alert.success'), $this->repo->forEmployee($staffId));
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    /** Tells the app which coordinator-only screens/buttons to actually
     *  show -- without this, the app had no way to know and was showing
     *  "Assign a Task" to everyone with portal_access, not just managers. */
    public function myPermissions()
    {
        return $this->responseWithSuccess(___('alert.success'), [
            'can_manage' => hasPermission('portal_manage'),
        ]);
    }

    /** For the "Assign to" dropdown -- active staff, same list the website's
     *  Assign Task screen uses. */
    public function assignableStaff()
    {
        try {
            $staff = Staff::with('role')->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'role_id']);
            return $this->responseWithSuccess(___('alert.success'), $staff);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $assignedToRule = $request->boolean('paid') ? 'nullable' : 'required';

            $request->validate([
                'title'       => 'required|string|max:150',
                'assigned_to' => $assignedToRule . '|exists:staff,id',
                'amount'      => 'required_if:paid,1|nullable|numeric|min:0',
                'due_date'    => 'required|date',
                'priority'    => 'nullable|in:Low,Medium,High',
            ]);

            $result = $this->repo->store($request, Auth::id());

            if ($result['status']) {
                return $this->responseWithSuccess($result['message'], $result['data']);
            }
            return $this->responseWithError($result['message']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseWithError(___('alert.validation_error'), $e->errors());
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}
