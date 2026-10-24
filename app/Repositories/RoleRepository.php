<?php

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use App\Interfaces\RoleInterface;

class RoleRepository implements RoleInterface
{

    private $model;

    public function __construct(Role $roleModel)
    {
        $this->model = $roleModel;
    }

    public function student_parent_roles()
    {
        return $this->model->active()->whereIn('id', [6,7])->get();
    }

    public function all()
    {
        return $this->model->active()->get();
    }

    public function getAll()
    {
        return Role::latest()->paginate(10);
    }

    public function store($request)
    {
        try {
            $roleStore              = new $this->model;
            $roleStore->name        = $request->name;
            $roleStore->slug        = Str::slug($request->name);
            $roleStore->status      = $request->status;
            $roleStore->permissions = is_array($request->permissions) ? $request->permissions : [];
            $roleStore->leave_field_visibility = $this->normalizeLeaveVisibility($request->leave_field_visibility);
            $roleStore->save();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /** Casts every submitted role id to int and drops anything that isn't
     *  one of the two known fields, so stray form input can't pollute the
     *  stored JSON. */
    private function normalizeLeaveVisibility($raw): array
    {
        $raw = is_array($raw) ? $raw : [];
        $result = [];
        foreach (['replacement_day', 'half_day_full_day'] as $field) {
            $result[$field] = array_values(array_unique(array_map('intval', (array) ($raw[$field] ?? []))));
        }
        return $result;
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($request, $id)
    {
        try {
            if($id == 1)
                return false;

            $roleUpdate              = $this->model->findOrfail($id);
            $roleUpdate->name        = $request->name;
            $roleUpdate->status      = $request->status;
            $roleUpdate->permissions = is_array($request->permissions) ? $request->permissions : [];
            $roleUpdate->leave_field_visibility = $this->normalizeLeaveVisibility($request->leave_field_visibility);
            $roleUpdate->save();

            // Keep users.permissions aligned with the role template (middleware also intersects role ∩ user).
            $permPayload = $roleUpdate->permissions ?? [];
            User::where('role_id', (int) $id)->chunkById(200, function ($users) use ($permPayload) {
                foreach ($users as $user) {
                    $user->permissions = $permPayload;
                    $user->saveQuietly();
                }
            });

            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public function destroy($id)
    {
        try {
            if($id <= 7)
                return false;

            $roleDestroy = $this->model->find($id);
            $roleDestroy->delete();
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }
}
