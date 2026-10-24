<?php

namespace App\Repositories\Attendance;

use App\Traits\ReturnFormatTrait;
use App\Models\StudentAbsentNotification;
use App\Interfaces\Attendance\AttendanceNotificationInterface;


class StudentAbsenceNotificationRepository implements AttendanceNotificationInterface
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(StudentAbsentNotification $model)
    {
        $this->model  = $model;
    }

    public function setting(){

        return $this->model->firstOrCreate([], [
            'sending_time' => json_encode([]),
            'notification_message' => 'Hi [guardian_name] , your child [student_name] on class [class] - ([section]) Admission [admission_no] is [attendance_type] on [attendance_date]  . For more contact [school_name]',
        ]);

    }

    public function update($request){
        $send_times = [];
        foreach($request->shift_ids ?? [] as $key => $shift){
            $send_times[$shift] = $request->sending_times[$key] ?? null;
        }
        $setting =  $this->setting();
        $setting->notify_student = $request->notify_student ?? 0 ;
        $setting->notify_gurdian = $request->notify_gurdian  ?? 0;
        $setting->sending_time =  json_encode($send_times) ;
        $setting->active_status = $request->active_status ;
        $setting->notification_message = $request->notification_message ?? $setting->notification_message;
        $setting->save();
        return $this->responseWithSuccess(___('alert.submitted_successfully'), []);
    }


}
