<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use App\Models\Staff\Staff;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramClassMark extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'graded_at' => 'datetime',
        'marks'     => 'float',
    ];

    public const QUIZ       = 'quiz';
    public const PROJECT    = 'project';
    public const WORKSHEET  = 'worksheet';
    public const ASSIGNMENT = 'assignment';

    public const TYPES = [self::QUIZ, self::PROJECT, self::WORKSHEET, self::ASSIGNMENT];

    public function isGraded(): bool
    {
        return $this->marks !== null;
    }

    public function programClass(): BelongsTo
    {
        return $this->belongsTo(ProgramClass::class, 'program_class_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function grader(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'graded_by');
    }
}
