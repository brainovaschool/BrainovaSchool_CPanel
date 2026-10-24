<?php

namespace App\Http\Controllers\Backend;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Interfaces\RoleInterface;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use App\Interfaces\PermissionInterface;
use App\Http\Requests\Role\RoleStoreRequest;
use App\Http\Requests\Role\RoleUpdateRequest;

class RoleController extends Controller
{
    private $role;
    private $permission;

    function __construct(RoleInterface $role, PermissionInterface $permission)
    {

        if (!Schema::hasTable('settings') && !Schema::hasTable('users')  ) {
            abort(400);
        } 
        $this->role       = $role; 
        $this->permission = $permission; 
    }

    public function index()
    {
        $data['roles'] = $this->role->getAll();
        $data['title'] = ___('common.roles');
        return view('backend.roles.index', compact('data'));
    }

    public function create()
    {
        $data['title']             = ___('common.create_role');
        $data['permissionGroups']  = group_permissions_by_category($this->permission->all());
        $data['roles']             = $this->role->all();
        return view('backend.roles.create', compact('data'));
    }

    public function store(RoleStoreRequest $request)
    {
        $result = $this->role->store($request);
        if($result){
            return redirect()->route('roles.index')->with('success', ___('alert.role_created_successfully'));
        }
        return redirect()->route('roles.index')->with('danger', ___('alert.something_went_wrong_please_try_again') );
    }

    public function edit($id)
    {
        $data['role']              = $this->role->show($id);
        $data['title']             = ___('common.roles');
        $data['permissionGroups']  = group_permissions_by_category($this->permission->all());
        $data['roles']             = $this->role->all();
        return view('backend.roles.edit', compact('data'));
    }

    public function update(RoleUpdateRequest $request, $id)
    {
        $result = $this->role->update($request, $id);
        if($result){
            return redirect()->route('roles.index')->with('success', ___('alert.role_updated_successfully'));
        }
        return redirect()->route('roles.index')->with('danger', ___('alert.something_went_wrong_please_try_again'));
    }

    public function delete($id)
    {
        if($this->role->destroy($id)):
            $success[0] = ___('alert.deleted_successfully');
            $success[1] = 'success';
            $success[2] = ___('alert.deleted');
            $success[3] = ___('alert.OK');
            return response()->json($success);
        else:
            $success[0] = ___('alert.something_went_wrong_please_try_again');
            $success[1] = 'error';
            $success[2] = ___('alert.oops');
            return response()->json($success);
        endif;      
    }
}
