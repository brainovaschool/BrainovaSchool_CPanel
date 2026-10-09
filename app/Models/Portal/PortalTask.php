<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalTask extends Model
{
    protected $table = 'portal_tasks';
    protected $guarded = ['id'];

    public const OPEN          = 'open'; // phase 7 (paid, unclaimed)
    public const ASSIGNED      = 'assigned';
    public const IN_PROGRESS   = 'in_progress';
    public const SUBMITTED     = 'submitted';
    public const UNDER_REVIEW  = 'under_review';
    public const REVISION      = 'revision';
    public const COMPLETED     = 'completed';

    public const OVERDUE_STATUSES = [self::ASSIGNED, self::IN_PROGRESS, self::REVISION];

    protected $casts = [
        'urgent'       => 'boolean',
        'paid'         => 'boolean',
        'assigned_date' => 'date',
        'due_date'      => 'date',
        'completed_at'  => 'datetime',
        'claimed_at'    => 'datetime',
    ];

    public function assignee()
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function refUpload()
    {
        return $this->belongsTo(Upload::class, 'ref_upload_id');
    }

    public function submissions()
    {
        return $this->hasMany(PortalTaskSubmission::class, 'task_id')->orderBy('n');
    }

    public function comments()
    {
        return $this->hasMany(PortalTaskComment::class, 'task_id')->orderBy('created_at');
    }

    public function payout()
    {
        return $this->hasOne(PortalPayout::class, 'task_id');
    }

    /** Submitted work waiting on review is never "overdue" even past its
     *  due date — only work still sitting with the employee is. */
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, self::OVERDUE_STATUSES, true)
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function revisionCount(): int
    {
        return $this->submissions()->where('result', PortalTaskSubmission::REVISION)->count();
    }
}
