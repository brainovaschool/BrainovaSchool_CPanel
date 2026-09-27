<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSkillMastery extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'last_practiced_at' => 'datetime',
        'mastered_at'       => 'datetime',
        'milestone_seen_at' => 'datetime',
        'next_review_at'    => 'datetime',
    ];

    /** mastery_level itself stays these plain technical words everywhere
     *  in the code (filtering, counting, comparisons) — only the words
     *  shown to a family change, via stageLabel() below. Growth-metaphor
     *  defaults per the product plan; admin can rename them in Website
     *  Setup without touching what mastery_level actually stores. */
    public const DEFAULT_STAGE_LABELS = [
        'not_started' => 'Seed',
        'developing'  => 'Sprout',
        'proficient'  => 'Bloom',
        'advanced'    => 'Mighty Tree',
    ];

    public static function stageLabels(): array
    {
        $overrides = json_decode((string) setting('mastery_stage_labels'), true) ?: [];

        return array_merge(self::DEFAULT_STAGE_LABELS, array_filter($overrides));
    }

    public static function stageLabel(string $level): string
    {
        return self::stageLabels()[$level] ?? self::DEFAULT_STAGE_LABELS[$level] ?? ucfirst(str_replace('_', ' ', $level));
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class, 'skill_id', 'id');
    }
}
