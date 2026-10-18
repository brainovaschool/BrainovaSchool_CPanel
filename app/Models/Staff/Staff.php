<?php

namespace App\Models\Staff;

use App\Models\Academic\SubjectAssign;
use App\Models\Academic\SubjectAssignChildren;
use App\Models\BaseModel;
use App\Models\Gender;
use App\Models\Role;
use App\Models\Staff\Designation;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\LiveChat\Entities\Message;
class Staff extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'upload_documents' => 'array',
    ];

     protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'role_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function gender()
    {
        return $this->belongsTo(Gender::class, 'gender_id', 'id');
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class, 'receiver_id', 'user_id');
    }

    public function unreadMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id', 'user_id')->where('is_seen', 0);
    }

    public function subject_assign_childrens()
    {
        return $this->hasMany(SubjectAssignChildren::class, 'staff_id');
    }


    public function subjectAssigns()
    {
        return $this->hasManyThrough(
            SubjectAssign::class,            // The final model you want to access
            SubjectAssignChildren::class,    // The intermediate model
            'staff_id',                      // Foreign key on the SubjectAssignChildren table
            'id',                            // Foreign key on the SubjectAssign table (default is 'id')
            'id',                            // Local key on the Staff model (default is 'id')
            'subject_assign_id'              // Local key on the SubjectAssignChildren model
        );
    }

    /** The coordinator(s)/supervisor(s) who oversee THIS staff member. */
    public function coordinators()
    {
        return $this->belongsToMany(Staff::class, 'staff_coordinators', 'staff_id', 'coordinator_staff_id');
    }

    /** The staff member(s) THIS person coordinates/supervises. */
    public function coordinatees()
    {
        return $this->belongsToMany(Staff::class, 'staff_coordinators', 'coordinator_staff_id', 'staff_id');
    }
}
