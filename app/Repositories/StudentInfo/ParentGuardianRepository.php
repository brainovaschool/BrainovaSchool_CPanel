<?php

namespace App\Repositories\StudentInfo;

use App\Models\Role;
use App\Models\SiblingFeesDiscount;
use App\Models\StudentInfo\Student;
use App\Models\User;
use App\Enums\Settings;
use App\Enums\ApiStatus;
use Illuminate\Support\Str;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\StudentInfo\ParentGuardian;
use App\Interfaces\StudentInfo\ParentGuardianInterface;

class ParentGuardianRepository implements ParentGuardianInterface
{
    use ReturnFormatTrait;
    use CommonHelperTrait;

    private $model;

    public function __construct(ParentGuardian $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->active()->pluck('guardian_name','id')->toArray();
    }

    public function get()
    {
        return $this->model->select("id", "guardian_name")->active()->get();
    }

    public function getPaginateAll()
    {
        return $this->model::latest()->paginate(Settings::PAGINATE);
    }

    public function searchParent($request)
    {
        return $this->model::where('guardian_name', 'LIKE', "%{$request->keyword}%")
        ->orWhere('guardian_email', 'LIKE', "%{$request->keyword}%")
        ->orWhere('guardian_mobile', 'LIKE', "%{$request->keyword}%")
        ->paginate(Settings::PAGINATE);
    }

    public function getParent($request)
    {
        return $this->model->where('guardian_name', 'like', '%' . $request->text . '%')->pluck('guardian_name','id')->toArray();
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $role                     = Role::find(7); // Guardian role id 7

            $user                    = new User();
            $user->name              = $request->guardian_name;
            $user->email             = $request->guardian_email;
            $user->phone             = $request->guardian_mobile;
            $user->password          = $request->password_type == 'default'? Hash::make('123456') : Hash::make($request->password);
            $user->email_verified_at = now();
            $user->role_id           = $role->id;
            $user->permissions       = $role->permissions;
            $user->username          = $request->username;
            $user->upload_id         = $this->UploadImageCreate($request->guardian_image, 'backend/uploads/users');
            $user->uuid              = Str::uuid();
            $user->save();

            $row                      = new $this->model;
            $row->user_id             = $user->id;
            $row->father_name         = $request->father_name;
            $row->father_mobile       = $request->father_mobile;
            $row->father_profession   = $request->father_profession;
            $row->father_nationality   = $request->father_nationality;
            $row->mother_name         = $request->mother_name;
            $row->mother_mobile       = $request->mother_mobile;
            $row->mother_profession   = $request->mother_profession;
            $row->guardian_profession = $request->guardian_profession;
            $row->guardian_address    = $request->guardian_address;
            $row->guardian_relation   = $request->guardian_relation;
            $row->guardian_name       = $request->guardian_name;
            $row->guardian_email      = $request->guardian_email;
            $row->guardian_mobile     = $request->guardian_mobile;
            $row->guardian_image      = $user->upload_id;
            $row->father_image        = $this->UploadImageCreate($request->father_image, 'backend/uploads/users');
            $row->mother_image        = $this->UploadImageCreate($request->mother_image, 'backend/uploads/users');
            $row->status              = $request->status;
            $row->father_id           = $request->father_id;
            $row->mother_id           = $request->mother_id;
            $row->staff_id            = is_numeric($request->staff_id) ? $request->staff_id : null;
            $row->guardian_place_of_work = $request->guardian_place_of_work;
            $row->guardian_position      = $request->guardian_position;

            $row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);

        }
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row                      = $this->model->find($id);

            $user                     = User::find($row->user_id);

            $role                     = Role::find($user->role_id);

            $user->name               = $request->guardian_name;
            $user->email              = $request->guardian_email;
            $user->phone              = $request->guardian_mobile;
            $user->upload_id          = $this->UploadImageUpdate($request->guardian_image, 'backend/uploads/users',$user->upload_id);
            $user->permissions        = $role->permissions;
            $user->username           = $request->username;
            $user->save();

            $row->father_name         = $request->father_name;
            $row->father_mobile       = $request->father_mobile;
            $row->father_profession   = $request->father_profession;
            $row->father_nationality  = $request->father_nationality;
            $row->mother_name         = $request->mother_name;
            $row->mother_mobile       = $request->mother_mobile;
            $row->mother_profession   = $request->mother_profession;
            $row->guardian_profession = $request->guardian_profession;
            $row->guardian_address    = $request->guardian_address;
            $row->guardian_relation   = $request->guardian_relation;
            $row->guardian_name       = $request->guardian_name;
            $row->guardian_email      = $request->guardian_email;
            $row->guardian_mobile     = $request->guardian_mobile;
            $row->guardian_image      = $user->upload_id;
            $row->father_image        = $this->UploadImageUpdate($request->father_image, 'backend/uploads/users',$row->father_image);
            $row->mother_image        = $this->UploadImageUpdate($request->mother_image, 'backend/uploads/users',$row->mother_image);
            $row->status              = $request->status;
            $row->father_id           = $request->father_id;
            $row->mother_id           = $request->mother_id;

            $row->guardian_place_of_work = $request->guardian_place_of_work;
            $row->guardian_position      = $request->guardian_position;

            $row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = $this->model->find($id);
            $this->UploadImageDelete($row->father_image);
            $this->UploadImageDelete($row->mother_image);
            $row->delete();

            $user = User::find($row->user_id);
            $this->UploadImageDelete($user->upload_id);
            $user->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->destroy($id)['status']) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->model->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function getStudentsByParent($parentId)
    {
        $students =  Student::select('id','admission_no', 'roll_no', 'first_name', 'last_name', 'mobile',
            'email', 'dob', 'admission_date')
            ->with('session_class_student.class')
            ->where('parent_guardian_id', $parentId)
            ->get();

        $siblingsCount =  $students->count();
        $siblingDiscount = SiblingFeesDiscount::where('siblings_number', '<=', $siblingsCount)
            ->orderByDesc('siblings_number')
            ->first();

        return [
            'children' => $students,
            'siblingsCount' => $siblingsCount,
            'siblingDiscount' => $siblingDiscount ? $siblingDiscount->discount_percentage : null,
            'isEligible' => (bool)$siblingDiscount,
        ];
    }
}
