<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalWeeklyGoal extends Model
{
    protected $table = 'portal_weekly_goals';
    protected $guarded = ['id'];

    protected $casts = [
        'week_start' => 'date',
        'achieved'   => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
