<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentDailyGoal extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'goal_date'    => 'date',
        'completed_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }
}
