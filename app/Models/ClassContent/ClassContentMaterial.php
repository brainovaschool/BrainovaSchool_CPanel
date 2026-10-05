<?php

namespace App\Models\ClassContent;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassContentMaterial extends BaseModel
{
    protected $guarded = ['id'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ClassContentLesson::class, 'lesson_id');
    }
}
