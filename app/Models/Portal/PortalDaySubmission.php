<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalDaySubmission extends Model
{
    protected $table = 'portal_day_submissions';
    protected $guarded = ['id'];

    protected $casts = [
        'date'          => 'date',
        'submitted_at'  => 'datetime',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
