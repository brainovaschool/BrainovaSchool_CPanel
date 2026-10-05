<?php

namespace App\Models\ClassContent;

use App\Models\Academic\Classes;
use App\Models\Academic\Section;
use App\Models\Academic\Subject;
use App\Models\BaseModel;
use App\Models\Session;
use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassContentModule extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'coordinator_reviewed_at' => 'datetime',
        'approved_at'             => 'datetime',
    ];

    public const DRAFT               = 'draft';
    public const SUBMITTED           = 'submitted';
    public const CHANGES_REQUESTED   = 'changes_requested';
    public const COORDINATOR_REVIEWED = 'coordinator_reviewed';
    public const APPROVED            = 'approved';

    public const REVIEW_STATUSES = [
        self::DRAFT               => 'Draft',
        self::SUBMITTED           => 'Submitted for review',
        self::CHANGES_REQUESTED   => 'Changes requested',
        self::COORDINATOR_REVIEWED => 'Reviewed — awaiting admin approval',
        self::APPROVED            => 'Approved — live for students',
    ];

    public function scopeApproved($query)
    {
        return $query->where('review_status', self::APPROVED);
    }

    public function isApproved(): bool
    {
        return $this->review_status === self::APPROVED;
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'session_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class, 'classes_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
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

    public function lessons(): HasMany
    {
        return $this->hasMany(ClassContentLesson::class, 'module_id')->orderBy('sort_order');
    }
}
