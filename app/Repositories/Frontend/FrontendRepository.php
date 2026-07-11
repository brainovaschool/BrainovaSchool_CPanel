<?php

namespace App\Repositories\Frontend;

use App\Models\News;
use App\Enums\Status;
use App\Models\Event;
use App\Models\Slider;
use App\Models\Counter;
use App\Models\Gallery;
use App\Models\Session;
use App\Models\NoticeBoard;
use App\Models\Staff\Staff;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\GalleryCategory;
use App\Jobs\NotificationSendJob;
use App\Traits\CommonHelperTrait;
use App\Models\WebsiteSetup\About;
use App\Models\Academic\ClassSetup;
use App\Models\StudentInfo\Student;
use App\Models\Examination\ExamType;
use App\Models\WebsiteSetup\Contact;
use App\Models\Examination\ExamAssign;
use App\Models\Examination\MarksGrade;
use App\Models\WebsiteSetup\Subscribe;
use App\Models\WebsiteSetup\ContactInfo;
use App\Models\Examination\MarksRegister;
use App\Models\Academic\ClassSetupChildren;
use App\Models\WebsiteSetup\OnlineAdmission;
use App\Interfaces\Frontend\FrontendInterface;
use App\Models\WebsiteSetup\DepartmentContact;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\OnlineAdmissionFeesAssign;
use App\Models\WebsiteSetup\OnlineAdmissionSetting;

class FrontendRepository implements FrontendInterface
{

    use CommonHelperTrait;

    public function sliders()
    {
        return Slider::where('status', Status::ACTIVE)->orderBy('serial')->get();
    }
    public function counters()
    {
        return Counter::where('status', Status::ACTIVE)->orderBy('serial')->paginate(4);
    }

    public function online_admission_setting()
    {
        return OnlineAdmissionSetting::where('field','admission_payment')->first();
    }

    // Abouts
    public function abouts()
    {
        $data['abouts']   = About::where('status', Status::ACTIVE)->orderBy('serial')->get();
        $data['teachers'] = Staff::where('status', Status::ACTIVE)->where('role_id', 5)->take(8)->get();
        return $data;
    }

    // News
    public function news()
    {
        return News::where('status', Status::ACTIVE)->where('publish_date', '<=', date('Y-m-d'))->orderBy('id', 'desc')->paginate(6);
    }

    public function latestNews()
    {
        return News::where('status', Status::ACTIVE)->where('publish_date', '<=', date('Y-m-d'))->orderBy('id', 'desc')->take(4)->get();
    }

    public function newsDetail($id)
    {
        return News::where('status', Status::ACTIVE)->where('id', $id)->first();
    }

    // News
    public function notices()
    {
        $currentDateTime = Carbon::now(); // Get the current datetime
        return  NoticeBoard::where('status', Status::ACTIVE)->where('is_visible_web', 1)->where('publish_date', '<=', $currentDateTime)->orderBy('id', 'desc')->paginate(6);
    }


    public function noticeDetail($id)
    {
        return NoticeBoard::where('status', Status::ACTIVE)->where('id', $id)->first();
    }

    // Events
    public function events()
    {
        return Event::where('session_id', setting('session'))->where('status', Status::ACTIVE)->orderBy('id', 'desc')->paginate(6);
    }

    public function eventDetail($id)
    {
        return Event::where('session_id', setting('session'))->where('status', Status::ACTIVE)->where('id', $id)->first();
    }

    public function comingEvents()
    {
        return Event::where('session_id', setting('session'))->where('status', Status::ACTIVE)->where('date', '>=', date('Y-m-d'))->orderBy('date','DESC')->take(4)->get();
    }

    // Gallery
    public function galleryCategory()
    {
        return GalleryCategory::where('status', Status::ACTIVE)->orderBy('name')->get();
    }
    public function gallery()
    {
        return Gallery::where('status', Status::ACTIVE)->orderBy('id', 'desc')->paginate(12);
    }

    // Result
    public function getClasses($request)
    {
        return ClassSetup::active()->where('session_id', $request->session)->with('class')->get();
    }
    public function getSections($request)
    {
        $result = ClassSetup::active()->where('session_id', $request->session)->where('classes_id', $request->class)->first();
        return ClassSetupChildren::with('section')->where('class_setup_id', @$result->id)->select('section_id')->get();
    }
    public function getExamType($request)
    {
        return ExamAssign::where('session_id', $request->session)
        ->where('classes_id',$request->class)
        ->where('section_id',$request->section)
        ->select('exam_type_id')
        ->distinct()
        ->with('exam_type')
        ->get();
    }
    public function result()
    {
        $data['sessions'] = Session::where('status', Status::ACTIVE)->orderBy('name')->get();
        return $data;
    }
    // end result

    public function searchResult($request)
    {
        $classSection   = SessionClassStudent::where('session_id', $request->session)
            ->where('classes_id', $request->class)
            ->where('section_id', $request->section)
            ->whereHas('student', function ($query) use ($request) {
                return $query->where('admission_no', $request->admission_no);
            })
            ->first();

        $marks_registers = MarksRegister::where('exam_type_id', $request->exam)
            ->where('classes_id', @$classSection->classes_id)
            ->where('section_id', @$classSection->section_id)
            ->where('session_id', $request->session)
            ->with('marksRegisterChilds', function ($query) use ($classSection) {
                $query->where('student_id', $classSection->student_id);
            })->get();

        $result      = ___('examination.Passed');
        $total_marks = 0;

        if ($marks_registers->count() == 0)
            return false;

        foreach ($marks_registers as $marks_register) {
            $total_marks += $marks_register->marksRegisterChilds->sum('mark');
            if ($marks_register->marksRegisterChilds->sum('mark') < examSetting('average_pass_marks')) {
                $result = ___('examination.Failed');
            }
        }

        $grades = MarksGrade::where('session_id', $request->session)->get();
        $gpa = '';
        foreach ($grades as $grade) {
            if ($grade->percent_from <= $total_marks / count($marks_registers) && $grade->percent_upto >= $total_marks / count($marks_registers)) {
                $gpa = $grade->point;
            }
        }

        $data = [];
        $data['classSection']    = $classSection;
        $data['marks_registers'] = $marks_registers;
        $data['result']          = $result;
        $data['gpa']             = $gpa;
        $data['avg_marks']       = $total_marks / count($marks_registers);
        return $data;
    }


    // Contact Information

    public function contactInfo(){
        return ContactInfo::where('status', Status::ACTIVE)->get();
    }

    public function depContact(){
        return DepartmentContact::where('status', Status::ACTIVE)->get();
    }

    public function onlineAdmission($request){
        try {
            $row                 = new OnlineAdmission();
            $row->reference_no   = Str::random(6);
            $row->first_name     = $request->student_name;
            $row->last_name      = null;
            $row->student_age    = $request->student_age;
            $row->student_class  = $request->student_class;
            $row->program        = $request->program;
            $row->guardian_name  = $request->parent_name;
            $row->guardian_phone = $request->parent_phone;
            $row->phone          = $request->parent_phone;
            $row->email          = $request->parent_email;
            $row->payment_status = 0;
            $row->save();

            $data = [];
            $data['student_name'] = $row->first_name;
            $data['class'] = $row->student_class;
            $data['program'] = $row->program;
            $data['admission_date'] = dateFormat(Carbon::now());
            $data['url'] = route('online-admissions.edit', $row->id);

            if(env('NOTIFICATION_JOB') == 'queue'){
                dispatch(new NotificationSendJob('Online_Admission', [1], $data , ['Super Admin']));
            }else{
                dispatch(new NotificationSendJob('Online_Admission', [1], $data , ['Super Admin']))->handle();
            }

            return $row;
        } catch (\Throwable $th) {
            return null;
        }
    }


    public function storeOnlineAdmissionFees($request){
        try {
            $admission = $this->onlineAdmissionDetail($request->id);
            $assgined_fees =  $this->onlineAdmissionFees($admission->session_id , $admission->classes_id ,$admission->section_id);
            $admission->payment_status = 1;
            $admission->payslip_image_id = $this->UploadImageCreate($request->payment_image, 'backend/uploads/uploadDocuments');
            $admission->fees_assign_id = $assgined_fees ? $assgined_fees->id : null;
            $admission->save();
            return $admission;
        } catch (\Throwable $th) {
            dd($th);
            return response()->json([___('frontend.Error'), ___('frontend.something_went_wrong'), 'error', ___('frontend.OK')]);
        }
    }

    public function onlineAdmissionDetail($id)
    {
        return OnlineAdmission::findOrFail($id);
    }

    public function onlineAdmissionFees($session_id , $class_id , $section_id = null)
    {
       return  OnlineAdmissionFeesAssign::where('session_id',$session_id)->where('class_id',$class_id)->when( !is_null($section_id),function ($query, $section_id){
            return $query->where('section_id' , $section_id);
        })->first();
    }

    public function contact($request){
        try {
            $row          = new Contact();
            $row->name    = $request->name;
            $row->phone   = $request->phone;
            $row->email   = $request->email;
            $row->subject = $request->subject;
            $row->message = $request->message;
            $row->save();
            return response()->json([___('frontend.Success'), ___('frontend.send_successfully'), 'success', ___('frontend.OK')]);
        } catch (\Throwable $th) {
            return response()->json([___('frontend.Error'), ___('frontend.something_went_wrong'), 'error', ___('frontend.OK')]);
        }
    }

    public function subscribe($request){
        try {
            $row          = Subscribe::where('email', $request->email)->first();
            if($row)
                return response()->json([___('frontend.Attention'), ___('frontend.already_subscribed'), 'warning', ___('frontend.OK')]);

            $row          = new Subscribe();
            $row->email   = $request->email;
            $row->save();

            return response()->json([___('frontend.Success'),___('frontend.Subscribed'), 'success', ___('frontend.OK')]);

        } catch (\Throwable $th) {
            return response()->json([___('frontend.Error'), ___('frontend.something_went_wrong'), 'error', ___('frontend.OK')]);
        }
    }
}
