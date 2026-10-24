<?php

namespace App\Http\Controllers\Attendance;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\RoleRepository;
use App\Models\StudentAbsentNotification;
use App\Http\Requests\Attendance\StudentAbsentNotificationUpdate;
use App\Http\Requests\Attendance\StudentAbsentettingUpdateRequest;
use App\Repositories\Attendance\StudentAbsenceNotificationRepository;
use App\Http\Requests\Attendance\StudentAbsentNotificationUpdateRequest;
use App\Repositories\Academic\ShiftRepository;

class AttendaceNotificationController extends Controller
{
    protected $role_repo ;
    protected $student_absense_noti_repo ;
    protected $shift_repo;

    public function __construct(RoleRepository $role_repo , StudentAbsenceNotificationRepository $student_absense_noti_repo , ShiftRepository $shift_repo)
    {
        $this->role_repo = $role_repo;
        $this->student_absense_noti_repo = $student_absense_noti_repo;
        $this->shift_repo = $shift_repo;
    }


    public function notification(){
        $data = [];
        $data['pt'] = ___('settings.absent_notification_setup');
        $data['setting'] = $this->student_absense_noti_repo->setting();
        $data['shifts'] = $this->shift_repo->getAll();
        return view('backend.attendance.notification_setting', $data);
    }


    public function settinngUpdate(StudentAbsentettingUpdateRequest $request){
     
        $result =  $this->student_absense_noti_repo->update($request);

        if($result['status']){
            return redirect()->back()->with('success', $result['message']);
        }
        return back()->with('danger', $result['message']);
    }
}
