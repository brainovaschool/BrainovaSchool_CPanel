<?php

namespace App\Http\Controllers\Backend;

use App\Models\Staff\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use App\Interfaces\RoleInterface;
use App\Interfaces\UserInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Interfaces\PermissionInterface;
use App\Http\Requests\User\UserStoreRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Interfaces\GenderInterface;
use App\Interfaces\Staff\DepartmentInterface;
use App\Interfaces\Staff\DesignationInterface;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{

    private $user;
    private $permission;
    private $role;
    private $designation;
    private $department;
    private $gender;

    function __construct(
        UserInterface $user,
        PermissionInterface $permission,
        RoleInterface $role,
        DesignationInterface $designation,
        DepartmentInterface $department,
        GenderInterface $gender,

        )
    {

        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        }
        $this->user         = $user;
        $this->permission   = $permission;
        $this->role         = $role;
        $this->designation  = $designation;
        $this->department   = $department;
        $this->gender       = $gender;
    }

    public function index()
    {
        $data['users'] = $this->user->getAll();
        $data['title'] = ___('staff.staff');
        return view('backend.users.index', compact('data'));
    }

    public function create()
    {
        $data['title']         = ___('staff.create_staff');
        $data['permissions']   = $this->permission->all();
        $data['roles']         = $this->role->all();
        $data['designations']  = $this->designation->all();
        $data['departments']   = $this->department->all();
        $data['genders']       = $this->gender->all();
        return view('backend.users.create', compact('data'));
    }

    public function store(UserStoreRequest $request)
    {
        $result = $this->user->store( $request);
        if ($result == 2) {
            return redirect()->route('users.index')->with('danger', ___('alert.Staff limit is over.'));
        }
        elseif ($result == 1) {
            return redirect()->route('users.index')->with('success', ___('alert.user_created_successfully'));
        }
        return redirect()->route('users.index')->with('danger',  ___('alert.something_went_wrong_please_try_again'));
    }

    public function edit($id)
    {
        $data['user']          = $this->user->show($id);
        $data['title']         = ___('staff.update_staff');
        $data['permissions']   = $this->permission->all();
        $data['roles']         = $this->role->all();
        $data['designations']  = $this->designation->all();
        $data['departments']   = $this->department->all();
        $data['genders']       = $this->gender->all();
        // dd($data);
        return view('backend.users.edit', compact('data'));
    }

    public function show($id)
    {
        $data = $this->user->show($id);
        return view('backend.users.show', compact('data'));
    }

    public function update(UserUpdateRequest $request, $id)
    {
        $result = $this->user->update($request, $id);
        if ($result) {
            return redirect()->route('users.index')->with('success', ___('alert.user_updated_successfully'));
        }
        return redirect()->route('users.index')->with('danger',  ___('alert.something_went_wrong_please_try_again'));
    }

    public function delete($id)
    {
        if ($this->user->destroy($id)) :
            $success[0] = ___('alert.deleted_successfully');
            $success[1] = "Success";
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
        else :
            $success[0] = ___('alert.something_went_wrong_please_try_again');
            $success[1] = 'error';
            $success[2] = ___('alert.oops');
        endif;
        return response()->json($success);
    }

    public function changeRole(Request $request)
    {
        $data['role_permissions'] = $this->role->show($request->role_id)->permissions;
        $data['permissions']      = $this->permission->all();
        return view('backend.users.permissions', compact('data'))->render();
    }

    public function status(Request $request)
    {

        if ($request->type == 'active') {
            $request->merge([
                'status' => 1
            ]);
            $this->user->status($request);
        }

        if ($request->type == 'inactive') {
            $request->merge([
                'status' => 0
            ]);
            $this->user->status($request);
        }

        return response()->json(["message" => __("Status update successful")], Response::HTTP_OK);
    }

    public function deletes(Request $request)
    {
        $this->user->deletes($request);

        return response()->json(["message" => __('Delete successful.')], Response::HTTP_OK);
    }

    /**
     * These reuse the repository's existing deletes()/status() methods, which
     * already accept an array of ids — only the response shape here is new,
     * matching backend.partials.bulk-actions-ajax's expected format.
     */
    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $request->merge(['ids' => $ids]);
        $deleted = $this->user->deletes($request);
        if (!$deleted) {
            return response()->json([___('alert.something_went_wrong_please_try_again'), 'error', ___('alert.oops'), ___('alert.OK')]);
        }
        return response()->json([___('alert.deleted_successfully'), 'success', ___('alert.deleted'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $request->merge(['ids' => $ids, 'status' => (int) $request->input('status', 1)]);
        $this->user->status($request);
        return response()->json([___('alert.updated_successfully'), 'success', ___('alert.updated'), ___('alert.OK')]);
    }

    public function changePermission($id)
    {
        $staff = Staff::with('role', 'user')->find($id);
        $data['role'] = $staff->user->role;
        $data['title']       = ___('common.Change Permission');
        $data['user_permissions'] = $staff->user->permissions;
        $data['permissions'] = $this->permission->all();
        $data['staff'] = $staff;
        return view('backend.users.change_permission', compact('data'));
    }

    public function permissionUpdate(Request $request, $id)
    {
        $user = User::find($id);
        $user->permissions = $request->permissions;
        $user->save();
        return back()->with('success', ___('alert.permission_updated_successfully'));
    }
}
