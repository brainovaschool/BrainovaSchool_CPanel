<?php

namespace App\Repositories\StudentInfo;

use App\Models\Role;
use App\Models\User;
use App\Enums\Settings;
use Illuminate\Support\Str;
use App\Jobs\NotificationSendJob;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Models\StudentInfo\Student;
use Illuminate\Support\Facades\Hash;
use App\Traits\SendNotificationTrait;
use App\Models\StudentInfo\ParentGuardian;
use App\Models\WebsiteSetup\OnlineAdmission;
use App\Models\StudentInfo\SessionClassStudent;
use App\Interfaces\StudentInfo\OnlineAdmissionInterface;

class OnlineAdmissionRepository implements OnlineAdmissionInterface
{
    use ReturnFormatTrait;
    use CommonHelperTrait;
    use SendNotificationTrait;

    private $model;

    public function __construct(OnlineAdmission $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->orderBy('id','desc')->paginate(Settings::PAGINATE);
    }

    public function searchStudents($request)
    {
        $result = $this->model;

        if($request->class != "") {
            $result = $result->where('classes_id', $request->class);
        }
        if($request->section != "") {
            $result = $result->where('section_id', $request->section);
        }
        if($request->keyword != "") {
            $keyword = $request->keyword;
            $result = $result->where(function ($query) use ($keyword) {
                $query->where('first_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('last_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('student_age', 'LIKE', "%{$keyword}%")
                    ->orWhere('student_class', 'LIKE', "%{$keyword}%")
                    ->orWhere('program', 'LIKE', "%{$keyword}%")
                    ->orWhere('phone', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhere('guardian_name', 'LIKE', "%{$keyword}%")
                    ->orWhere('guardian_phone', 'LIKE', "%{$keyword}%");
            });
        }

        return $result->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function store($request)
    {

        DB::beginTransaction();
        try {

            // if(Student::count() >= setting('student_limit'))
            if(Student::count() >= activeSubscriptionStudentLimit() && env('APP_SAAS'))
                return $this->responseWithError(___('alert.Your student limit is over.'), []);

            $super_admin_ids = User::where('role_id',1)->pluck('id')->toArray();
            $admission = OnlineAdmission::find($request->online_admission_id);

            // if ($request->has('document_names')) {
            //     dd($this->uploadDocuments($request));
            // }else{
            //     dd($admission->upload_documents);
            // }

            $role                     = Role::find(7); // Guardian role id 7

            $parent_user                     = new User();
            $parent_user->name               = $request->guardian_name;
            $parent_user->email              = $request->guardian_email;
            $parent_user->phone              = $request->guardian_mobile;
            $parent_user->password           = Hash::make('123456');
            $parent_user->email_verified_at  = now();
            $parent_user->role_id            = $role->id;
            $parent_user->permissions        = $role->permissions;
            $parent_user->upload_id          = $this->UploadImageCreate($request->guardian_image, 'backend/uploads/users');
            $parent_user->uuid               = Str::uuid();
            $parent_user->save();

            $parent                      = new ParentGuardian();
            $parent->user_id             = $parent_user->id;
            $parent->father_name         = $request->father_name;
            $parent->father_mobile       = $request->father_mobile;
            $parent->father_nationality = $request->father_nationality;
            $parent->father_profession   = $request->father_profession;
            $parent->mother_name         = $request->mother_name;
            $parent->mother_mobile       = $request->mother_mobile;
            $parent->mother_profession   = $request->mother_profession;
            $parent->guardian_profession = $request->guardian_profession;
            $parent->guardian_address    = $request->guardian_address;
            $parent->guardian_relation   = $request->guardian_relation;
            $parent->guardian_name       = $request->guardian_name;
            $parent->guardian_email      = $request->guardian_email;
            $parent->guardian_mobile     = $request->guardian_mobile;
            $parent->guardian_image      = $parent_user->upload_id;
            $parent->father_image        = $this->UploadImageCreate($request->father_image, 'backend/uploads/users');
            $parent->mother_image        = $this->UploadImageCreate($request->mother_image, 'backend/uploads/users');
            $parent->status              = $request->status;

            $parent->save();
            // End parent information


            // Student information
            $role                     = Role::find(6);// student role id 6

            $student_user                     = new User();
            $student_user->name               = $request->first_name.' '.$request->last_name;
            $student_user->email              = $request->email != ""? $request->email :  NULL;
            $student_user->phone              = $request->mobile != ""? $request->mobile :  NULL;
            $student_user->admission_no       = $request->admission_no;
            $student_user->password           = Hash::make('123456');
            $student_user->email_verified_at  = now();
            $student_user->role_id            = $role->id;
            $student_user->permissions        = $role->permissions;
            $student_user->date_of_birth      = $request->date_of_birth;
            $student_user->upload_id          = $request->has('image')?$this->UploadImageCreate($request->image, 'backend/uploads/students'):$admission->student_image_id;
            $student_user->uuid               = Str::uuid();
            $student_user->save();

            $row                       = new Student();
            $row->user_id              = $student_user->id;
            $row->first_name           = $request->first_name;
            $row->last_name            = $request->last_name;
            $row->admission_no         = $request->admission_no;
            $row->roll_no              = $request->roll_no != "" ? $request->roll_no :  NULL;
            $row->mobile               = $request->mobile;
            $row->image_id             = $student_user->upload_id ?? $admission->student_image_id;
            $row->email                = $request->email;
            $row->dob                  = $request->date_of_birth;
            $row->religion_id          = $request->religion != ""? $request->religion :  NULL;
            $row->gender_id            = $request->gender != ""? $request->gender :  NULL;
            $row->blood_group_id       = $request->blood != ""? $request->blood :  NULL;
            $row->admission_date       = $request->admission_date;
            $row->parent_guardian_id   = $parent->id;
            $row->student_category_id  = $request->category != ""? $request->category :  NULL;
            $row->status               = $request->status;
            $row->upload_documents     = $request->has('document_names') ? $this->uploadDocuments($request) : $admission->upload_documents;


            $row->place_of_birth = $request->place_of_birth;
            $row->nationality = $request->nationality;
            $row->cpr_no = $request->cpr_no;
            $row->spoken_lang_at_home = $request->spoken_lang_at_home;
            $row->residance_address = $request->residance_address;
            $row->save();

            $session_class                      = new SessionClassStudent();
            $session_class->session_id          = setting('session');
            $session_class->classes_id          = $request->class;
            $session_class->section_id          = $request->section != ""? $request->section :  NULL;
            $session_class->shift_id            = $request->shift != ""? $request->shift :  NULL;
            $session_class->student_id          = $row->id;
            $session_class->roll                = $request->roll_no;
            $session_class->save();
            // End student information


            // $admission->delete();

            $data = [];
            $data['student_name'] = @$row->student->first_name.' '.@$row->student->last_name;
            $data['admission_no'] = @$row->student->admission_no;
            $data['class'] = @$session_class->class->name;
            $data['section'] = @$session_class->section->name;
            $data['parent_name'] = $parent_user->name ;
            $data['admission_date'] = dateFormat($row->admission_date);
            $data['student_email'] = $student_user->email;
            $data['parent_email'] = $parent_user->email;

            DB::commit();

            // if(env('NOTIFICATION_JOB') == 'queue'){
            //     dispatch(new NotificationSendJob('Online_Admission', [$super_admin_ids], $data , ['Super Admin']));
            // }else{
            //     dispatch(new NotificationSendJob('Online_Admission', [$super_admin_ids], $data , ['Super Admin']))->handle();
            // }

            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();
            dd($th);
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }


    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = $this->model->find($id);
            $row->delete();
            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollback();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

}
