<?php

namespace App\Models;

use App\Enums\RoleEnum;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Model;

class LeaveRequest extends Model
{

    protected $fillable = [
        'leave_type_id',
        'user_id',
        'role_id',
        'request_by',
        'session_id',
        'start_date',
        'end_date',
        'description',
        'leave_days',
        'approval_status',
        'attachment_id',
        'is_half_day',
        'replacement_day',
    ];

    public function leaveType(){
        return $this->belongsTo(LeaveType::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'request_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    public function attachment()
    {
        return $this->belongsTo(Upload::class);
    }

    /** For the ID column: student leave shows the student's roll number,
     *  staff leave shows the staff id - resolved via the shared user_id,
     *  since leave_requests has no direct FK to either. */
    public function student()
    {
        return $this->hasOne(Student::class, 'user_id', 'user_id');
    }

    public function staff()
    {
        return $this->hasOne(Staff::class, 'user_id', 'user_id');
    }

    /** Roll number for a student row, staff id for a staff row - the ID
     *  column shown in the log and approval tables. */
    public function getIdentifierAttribute()
    {
        if ((int) $this->role_id === RoleEnum::STUDENT) {
            return optional($this->student->session_class_student ?? null)->roll ?? '—';
        }

        return $this->staff->id ?? '—';
    }

}
