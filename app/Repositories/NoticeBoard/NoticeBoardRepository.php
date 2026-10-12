<?php

namespace App\Repositories\NoticeBoard;

use App\Models\User;
use App\Enums\RoleEnum;
use App\Models\Session;
use App\Models\Homework;
use App\Models\Certificate;
use App\Models\NoticeBoard;
use App\Models\HomeworkStudent;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Models\NoticeBoardTranslate;
use Illuminate\Support\Facades\Auth;
use App\Models\StudentInfo\SessionClassStudent;
use App\Repositories\NoticeBoard\NoticeBoardInterface;
use Modules\PushNotification\Services\PushNotificationService;

class NoticeBoardRepository implements NoticeBoardInterface
{
    use ReturnFormatTrait, CommonHelperTrait;

    private $model;
    private $notice_board_trans;

    public function __construct(NoticeBoard $model, NoticeBoardTranslate $notice_board_trans)
    {
        $this->model = $model;
        $this->notice_board_trans = $notice_board_trans;
    }

    public function all()
    {
        return $this->model->orderByDesc('id')->get();
    }

    public function getPaginateAll()
    {
        $user  = Auth::user();


        $query = $this->model::query();
        if ($user->role_id != RoleEnum::SUPERADMIN) {
            if ($user->role_id == RoleEnum::STAFF || $user->role_id == RoleEnum::TEACHER) {
                $departmentId = @$user->staff->department_id;
                $query->whereJsonContains('visible_to', "$user->role_id");
            }

            $query->where(function ($query) use ($departmentId) {
                return $query->where('department_id', $departmentId)
                    ->orWhereNull('department_id');
            });
        }

        return $query->latest()->orderByDesc('id')->paginate(10);
    }

    public function search($request)
    {
        $rows = $this->model::query();
        if ($request->class != "") {
            $rows = $rows->where('classes_id', $request->class);
        }
        if ($request->section != "") {
            $rows = $rows->where('section_id', $request->section);
        }
        if ($request->subject != "") {
            $rows = $rows->where('subject_id', $request->subject);
        }
        return $rows->paginate(10);
    }

    public function store($request)
    {
        try {
            DB::beginTransaction();
            $student_parent_roles = [6, 7];
            $visibleRoles = $request->visible_to ?? [];

            $row                    = new $this->model;
            $row->title             = $request->title;
            $row->publish_date      = date('Y-m-d H:i:s', strtotime($request->publish_date));
            $row->date              = date('Y-m-d', strtotime($request->date));


            $row->is_visible_web       = $request->is_visible_web;
            $row->status               = $request->status;
            $row->description          = $request->description;
            $row->visible_to           = $request->visible_to;
            $row->department_id        = !empty($request->department_id) ? $request->department_id : null;
            if (!empty(array_intersect($student_parent_roles, $visibleRoles))) {
                $row->class_id   = !empty($request->class) ? $request->class : null;
                $row->section_id = !empty($request->section) ? $request->section : null;
                $row->student_id = !empty($request->student) ? $request->student : null;
            }

            $row->attachment           = $this->UploadImageCreate($request->attachment, 'backend/uploads/communication');
            $row->session_id = setting('session');
            $row->save();

            $en_row                   = new $this->notice_board_trans;
            $en_row->notice_board_id  = $row->id;
            $en_row->locale           = request()->locale ?? config('app.locale');
            $en_row->title             = $request->title;
            $en_row->description      = $request->description;
            $en_row->save();

            $selectedUsers = User::whereIn("role_id", $visibleRoles)
            ->where(function ($query) use ($request, $student_parent_roles) {
                $query->whereNotIn("role_id", $student_parent_roles)
                ->orWhere(function ($query) use ($request, $student_parent_roles) {
                    $query->whereIn("role_id", $student_parent_roles)
                    ->when($request->department_id, function ($query) use ($request) {
                        $query->whereHas("student", fn($q) => $q->where("department_id", $request->department_id));
                    })
                    ->when($request->section, function ($query) use ($request) {
                        $query->whereHas("student.session_class_student", fn($q) => $q->where("section_id", $request->section));
                    })
                    ->when($request->class, function ($query) use ($request) {
                        $query->whereHas("student.session_class_student", fn($q) => $q->where("classes_id", $request->class));
                    })
                    ->when($request->student, function ($query) use ($request) {
                        $query->whereHas("student.session_class_student", fn($q) => $q->where("student_id", $request->student));
                    });
                });
            })
            ->where("id", "!=", Auth::id())
            ->get();

            foreach ($selectedUsers as $selectedUser) {
                (new PushNotificationService())->browserNotification($selectedUser->id, 'New Notification', $request->title , "");
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            info($th->getMessage());
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function update($request, $id)
    {
        $student_parent_roles = [6, 7];
        DB::beginTransaction();
        try {
            $row                    = $this->model->find($id);
            $row->title             = $request->title;
            $row->publish_date      = date('Y-m-d H:i:s', strtotime($request->publish_date));
            $row->date              = date('Y-m-d', strtotime($request->date));

            $row->is_visible_web       = $request->is_visible_web;
            $row->status               = $request->status;
            $row->description          = $request->description;
            $row->visible_to           = $request->visible_to;
            if (!empty(array_intersect($student_parent_roles, $request->visible_to ?? []))) {
                $row->class_id                 = empty($request->class) ? $row->class_id : $request->class;
                $row->section_id               = empty($request->section) ? $row->section_id : $request->section;
                $row->student_id               = empty($request->student) ? $row->student_id : $request->student;
            }
            $row->attachment           = $this->UploadImageUpdate($request->attachment, 'backend/uploads/communication', $row->attachment);
            $row->save();

            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    'title' => $request->title,
                    'description' => $request->description,
                ]);
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
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
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($notice_board_id)
    {
        return $this->notice_board_trans->where('notice_board_id', $notice_board_id)->get()->groupBy('locale');
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->notice_board_trans->where('notice_board_id', $id)->delete();
            $notice_board = $this->show($id);

            foreach ($request->title as $key => $title) {
                $row                   = new $this->notice_board_trans;
                $row->notice_board_id        = $id;
                $row->locale           = $key;
                $row->title             = $title;
                $row->description      = isset($request->description[$key]) ? $request->description[$key] : $notice_board->description;
                $row->save();
            }

            // Guarantee the site's default-locale row always exists, even if it
            // wasn't among the submitted languages, so the public notice page
            // never ends up with a missing translation.
            $defaultLocale = config('app.locale');
            if (!isset($request->title[$defaultLocale])) {
                $row                       = new $this->notice_board_trans;
                $row->notice_board_id      = $id;
                $row->locale               = $defaultLocale;
                $row->title                = $notice_board->title;
                $row->description          = $notice_board->description;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}
