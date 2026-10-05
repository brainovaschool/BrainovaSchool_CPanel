<?php

namespace App\Models\ClassContent;

use App\Models\BaseModel;
use App\Traits\ResolvesVideoEmbed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassContentActivity extends BaseModel
{
    use ResolvesVideoEmbed;

    protected $guarded = ['id'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ClassContentLesson::class, 'lesson_id');
    }
}
