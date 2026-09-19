<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per accessory a student currently has equipped — accessories are
 *  the only avatar layer that can be worn several at once (glasses AND a
 *  backpack AND a held prop, all at the same time). */
class StudentAvatarEquippedAccessory extends BaseModel
{
    protected $guarded = ['id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(AvatarItem::class, 'avatar_item_id', 'id');
    }
}
