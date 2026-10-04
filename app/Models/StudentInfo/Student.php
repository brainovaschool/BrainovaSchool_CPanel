<?php

namespace App\Models\StudentInfo;

use App\Models\SpecialDiscount;
use App\Models\StudentSpecialDiscount;
use App\Models\User;
use Faker\Core\Blood;
use App\Models\Gender;
use App\Models\Upload;
use App\Models\Religion;
use App\Models\BaseModel;
use App\Models\BloodGroup;
use App\Models\Staff\Staff;
use App\Models\Academic\Shift;
use App\Models\Fees\FeesMaster;
use App\Models\Staff\Department;
use Modules\BehaviourRecord\Entities\StudentIncidentAssign;
use Modules\LiveChat\Entities\Message;
use App\Models\Fees\FeesAssignChildren;
use Illuminate\Database\Eloquent\Model;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\AssignFeesDiscount;
use App\Models\Fees\FeesCollect;
use Modules\VehicleTracker\Entities\EnrollmentReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\VehicleTracker\Entities\StudentRouteEnrollment;
use PhpParser\Node\Expr\Assign;

class Student extends BaseModel
{
    use HasFactory;

    protected $appends = ['full_name'];

    protected $guarded = ['id'];

    protected $casts = [
        'upload_documents' => 'array',
    ];

    public function routeEnroll()
    {
        return $this->hasOne(StudentRouteEnrollment::class, 'student_id', 'id');
    }

    public function staffs()
    {
        return $this->hasManyThrough(Staff::class, SubjectAssignChildren::class, 'student_id', 'staff_id');
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'image_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    /** Always the student's PRIMARY (main/grade) enrollment — a student
     *  can also hold any number of secondary (short-course) enrollments,
     *  which this deliberately excludes so every existing "the student's
     *  class" caller keeps working exactly as before. Order is a safety
     *  net only; application code never allows two primary rows to
     *  exist for the same student. */
    public function session_class_student()
    {
        return $this->belongsTo(SessionClassStudent::class, 'id', 'student_id')
            ->where('enrollment_type', SessionClassStudent::PRIMARY)
            ->latest('id');
    }

    /** All of this student's active enrollments (primary + any
     *  secondary short courses) — used wherever something should cover
     *  every class the student is in, not just their main one (homework,
     *  exams, attendance visibility). */
    public function session_class_students()
    {
        return $this->hasMany(SessionClassStudent::class, 'student_id', 'id');
    }

    /** Every (classes_id, section_id) pair this student is enrolled in
     *  THIS session — primary class plus any short courses. Pair with
     *  matchAnyClassSectionPair() wherever homework/exam/attendance
     *  visibility needs to cover all of a student's classes, not just
     *  their main one. */
    public function enrolledClassSectionPairs()
    {
        return $this->session_class_students()
            ->where('session_id', setting('session'))
            ->get(['classes_id', 'section_id']);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id', 'id');
    }

    public function religion()
    {
        return $this->belongsTo(Religion::class, 'religion_id', 'id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    public function blood()
    {
        return $this->belongsTo(BloodGroup::class, 'blood_group_id', 'id');
    }

    public function parent()
    {
        return $this->belongsTo(ParentGuardian::class, 'parent_guardian_id', 'id');
    }

    /** Same as session_class_student() above — kept as a separate method
     *  only because existing code calls it by this name too; both must
     *  stay in sync (primary enrollment only). */
    public function sessionStudentDetails()
    {
        return $this->belongsTo(SessionClassStudent::class, 'id', 'student_id')
            ->where('enrollment_type', SessionClassStudent::PRIMARY)
            ->latest('id');
    }

    public function studentCategory()
    {
        return $this->belongsTo(StudentCategory::class, 'student_category_id', 'id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'sender_id', 'user_id')->latest();
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id', 'user_id')->where('is_seen', 0);
    }

    public function route()
    {
        return $this->hasOne(StudentRouteEnrollment::class, 'student_id', 'id');
    }

    public function pickupReport()
    {
        return $this->hasOne(EnrollmentReport::class, 'student_id', 'id')->where('type', 'pickup');
    }

    public function dropReport()
    {
        return $this->hasOne(EnrollmentReport::class, 'student_id', 'id')->where('type', 'drop');
    }


    public function feesAssignChild()
    {
        return $this->hasMany(FeesAssignChildren::class);
    }

    public function behaviourRecord()
    {
        return $this->hasMany(StudentIncidentAssign::class)->latest();
    }




    public function feesPayments()
    {
        return $this->hasMany(FeesCollect::class);
    }

    public function feesMasters()
    {
        return $this->hasManyThrough(
            FeesMaster::class,
            FeesAssignChildren::class,
            'student_id',       // Foreign key on FeesAssignChildren
            'id',               // Local key on feesMaster
            'id',               // Local key on Student
            'fees_master_id'    // Foreign key on FeesAssignChildren
        );
    }

    public function feesDiscounts()
    {
        return $this->hasManyThrough(
            AssignFeesDiscount::class,
            FeesAssignChildren::class,
            'student_id',       // Foreign key on FeesAssignChildren
            'fees_assign_children_id',               // Local key on feesMaster
            'id',               // Local key on Student
            'id'    // Foreign key on FeesAssignChildren
        );
    }

    // Student.php
    public function specialDiscount()
    {
        return $this->hasOne(StudentSpecialDiscount::class, 'student_id');
    }

}
