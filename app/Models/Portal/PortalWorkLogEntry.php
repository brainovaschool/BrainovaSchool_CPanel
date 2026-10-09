<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalWorkLogEntry extends Model
{
    protected $table = 'portal_work_log_entries';
    protected $guarded = ['id'];

    protected $casts = [
        'date'      => 'date',
        'is_extra'  => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function task()
    {
        return $this->belongsTo(PortalTask::class, 'task_id');
    }

    /** "HH:MM" for form inputs and display — the DB cast leaves start_time/
     *  end_time as plain strings since a bare TIME column isn't Carbon-cast
     *  by default. */
    public function getStartLabelAttribute(): string
    {
        return substr($this->start_time, 0, 5);
    }

    public function getEndLabelAttribute(): string
    {
        return substr($this->end_time, 0, 5);
    }

    public function hours(): float
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end   = \Carbon\Carbon::parse($this->end_time);
        return round($end->diffInMinutes($start) / 60, 2);
    }
}
