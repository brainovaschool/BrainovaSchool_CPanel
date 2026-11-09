<?php

namespace App\Models\ClassContent;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassContentCompletion extends BaseModel
{
    protected $table = 'class_content_completions';
    protected $guarded = ['id'];

    public const LESSON   = 'lesson';
    public const ACTIVITY = 'activity';
    public const PROJECT  = 'project';
    public const QUIZ     = 'quiz';

    protected $casts = [
        'completed_at' => 'datetime',
        'xp_awarded'   => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
