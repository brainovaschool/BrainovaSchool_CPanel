<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\Academic\Classes;
use App\Models\Academic\Subject;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Skill extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'subject_id', 'classes_id', 'title', 'slug', 'description', 'sort_order', 'status',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id', 'id');
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'classes_id', 'id');
    }

    public function masteries(): HasMany
    {
        return $this->hasMany(StudentSkillMastery::class, 'skill_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }
}
