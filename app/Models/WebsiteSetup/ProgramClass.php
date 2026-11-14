<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramClass extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'submitted_at'            => 'datetime',
        'coordinator_reviewed_at' => 'datetime',
        'approved_at'             => 'datetime',
    ];

    public const DRAFT             = 'draft';
    public const SUBMITTED         = 'submitted';
    public const CHANGES_REQUESTED = 'changes_requested';
    public const APPROVED          = 'approved';

    public const REVIEW_STATUSES = [
        self::DRAFT             => 'Draft',
        self::SUBMITTED         => 'Submitted for review',
        self::CHANGES_REQUESTED => 'Changes requested',
        self::APPROVED          => 'Approved — live on Course Details',
    ];

    public function scopeApproved($query)
    {
        return $query->where('review_status', self::APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->review_status === self::APPROVED;
    }

    public function isEditableByTeacher(): bool
    {
        return in_array($this->review_status, [self::DRAFT, self::CHANGES_REQUESTED], true);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'coordinator_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'approved_by');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProgramClassMaterial::class, 'program_class_id')->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProgramClassActivity::class, 'program_class_id')->orderBy('sort_order');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(ProgramClassOutcome::class, 'program_class_id')->orderBy('sort_order');
    }

    public function hasProject(): bool
    {
        return (bool) $this->project_title;
    }

    public function hasQuiz(): bool
    {
        return (bool) $this->quiz_title;
    }
}
