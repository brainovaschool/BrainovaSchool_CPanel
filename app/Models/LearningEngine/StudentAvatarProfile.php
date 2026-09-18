<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAvatarProfile extends BaseModel
{
    protected $guarded = ['id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id', 'id');
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(AvatarItem::class, 'avatar_item_id', 'id');
    }

    public function accessory(): BelongsTo
    {
        return $this->belongsTo(AvatarItem::class, 'accessory_item_id', 'id');
    }
}
