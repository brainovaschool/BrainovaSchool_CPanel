<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mission extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'clue_lines' => 'array',
    ];

    /** The two real activity types a mission can be wrapped around in
     *  Phase 1. Deliberately a plain string column, not a real polymorphic
     *  relation — there are only ever these two, and this keeps
     *  StudentMissionRepository::progressFor() simple to read. */
    public const LINKABLE_TYPES = [
        'homework'    => 'Homework (incl. quiz-type)',
        'online_exam' => 'Online Examination',
    ];

    public const THEMES = [
        'treasure' => 'Treasure Hunt',
        'mystery'  => 'Mystery Box',
        'detective' => 'Detective Case Study',
        'adventure' => 'World Tour',
        'rescue'   => 'Rescue Mission',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function hub(): BelongsTo
    {
        return $this->belongsTo(AvatarItem::class, 'hub_id', 'id');
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(AvatarItem::class, 'building_id', 'id');
    }

    public function clueCount(): int
    {
        return count($this->clue_lines ?? []);
    }
}
