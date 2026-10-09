<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalDailyPlan extends Model
{
    protected $table = 'portal_daily_plans';
    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }
}
