<?php

namespace App\Models\Staff;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status'
    ];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }


    /** departments.staff_user_id holds the manager's USER id (it's a
     *  foreign key to `users`, per the migration that added it) — not a
     *  Staff id — so the owner key here has to be Staff::user_id, not the
     *  default Staff::id, or this would always miss. */
    public function manager()
    {
        return $this->belongsTo(Staff::class, 'staff_user_id', 'user_id');
    }
}
