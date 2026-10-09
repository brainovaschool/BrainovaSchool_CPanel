<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalAttendance extends Model
{
    protected $table = 'portal_attendances';
    protected $guarded = ['id'];

    protected $casts = [
        'date'           => 'date',
        'first_login_at' => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
