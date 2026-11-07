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

    /** How many tasks an employee can have sitting with them (Assigned,
     *  In Progress, or sent back for Revision) before claiming another
     *  open paid task is blocked - see TaskRepository::claim(). */
    public const MAX_OPEN_TASKS_FOR_CLAIM = 3;

    protected $casts = [
        'urgent'                => 'boolean',
        'paid'                  => 'boolean',
        'active_status'         => 'boolean',
        'assigned_date'         => 'date',
        'due_date'              => 'date',
        'completed_at'          => 'datetime',
        'claimed_at'            => 'datetime',
        'visible_to_staff_ids'  => 'array',
        'visible_to_role_ids'   => 'array',
        'hidden_from_staff_ids' => 'array',
    ];

    /** Archived-or-not, independent of the workflow `status` column -
     *  drives the default Tasks list and bulk Activate/Deactivate. */
    public function scopeActive($query)
    {
        return $query->where('active_status', true);
    }

    public function assignee()
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    /** Set only for tasks created from a Content Pool reel assignment. */
    public function reel()
    {
        return $this->belongsTo(PortalReel::class, 'reel_id');
    }

    /** category is a plain string copy, not a foreign key (same reasoning
     *  as PortalReel.series_name) - this matches it against the unique
     *  category name so a task can still reach its category's own SOP. */
    public function categoryModel()
    {
        return $this->belongsTo(PortalTaskCategory::class, 'category', 'name');
    }

    /** Three independent lists, checked in this order:
     *   1. hidden_from_staff_ids always wins - excluded is excluded, no
     *      matter what the designation or staff allow-list below say.
     *   2. If neither visible_to_role_ids nor visible_to_staff_ids is
     *      set, there's no restriction at all - open to everyone (minus
     *      step 1), same as before this field existed.
     *   3. Otherwise eligible if either list says yes (e.g. "all
     *      Coordinators" PLUS two named people who aren't Coordinators). */
    public function isVisibleTo(?int $staffId): bool
    {
        if ($staffId !== null && !empty($this->hidden_from_staff_ids) && in_array($staffId, $this->hidden_from_staff_ids, false)) {
            return false;
        }

        $roleIds  = $this->visible_to_role_ids;
        $staffIds = $this->visible_to_staff_ids;

        if (empty($roleIds) && empty($staffIds)) {
            return true;
        }

        if ($staffId === null) {
            return false;
        }

        if (!empty($staffIds) && in_array($staffId, $staffIds, false)) {
            return true;
        }

        if (!empty($roleIds)) {
            $staffRoleId = Staff::where('id', $staffId)->value('role_id');
            return $staffRoleId !== null && in_array((int) $staffRoleId, $roleIds, false);
        }

        return false;
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
