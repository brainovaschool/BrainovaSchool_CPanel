<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Where one student has dragged one owned "base" item to on their own
 *  island — see the migration for why this is personal, not shared. */
class StudentIslandPlacement extends BaseModel
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
