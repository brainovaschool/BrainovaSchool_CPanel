<?php

namespace App\Models;

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
        'replacement_day',
        'is_half_day',
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

}
