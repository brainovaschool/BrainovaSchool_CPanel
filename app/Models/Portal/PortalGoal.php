<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalGoal extends Model
{
    protected $table = 'portal_goals';
    protected $guarded = ['id'];

    public const NOT_STARTED = 'not_started';
    public const IN_PROGRESS = 'in_progress';
    public const DONE        = 'done';

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    public function owner()
    {
        return $this->belongsTo(Staff::class, 'owner_staff_id');
    }

    public function tasks()
    {
        return $this->hasMany(PortalTask::class, 'goal_id')->orderBy('due_date');
    }

    /** Every task assigned anywhere in this goal's own sub-tree, not just
     *  directly on it - a top-level goal's progress should reflect its
     *  sub-goals' and steps' tasks too. Relies on the repository having
     *  already attached each node's `childGoals` (see
     *  PortalGoalRepository::tree()) - a goal read on its own, without
     *  that relation set, is treated as childless rather than throwing. */
    public function collectTasks()
    {
        $all = $this->tasks instanceof \Illuminate\Support\Collection ? $this->tasks : collect();
        foreach ($this->getAttribute('childGoals') ?? [] as $child) {
            $all = $all->merge($child->collectTasks());
        }
        return $all;
    }

    /** Null (shown as "—") until at least one task exists anywhere in the
     *  sub-tree - a goal with no tasks yet isn't "0% done", it just hasn't
     *  started being tracked. */
    public function progressPercent(): ?int
    {
        $tasks = $this->collectTasks();
        if ($tasks->isEmpty()) {
            return null;
        }
        $done = $tasks->where('status', PortalTask::COMPLETED)->count();
        return (int) round($done / $tasks->count() * 100);
    }

    public function statusLabel(): string
    {
        return [
            self::NOT_STARTED => 'Not started',
            self::IN_PROGRESS => 'In progress',
            self::DONE        => 'Done',
        ][$this->status] ?? $this->status;
    }
}
