<?php

namespace App\Models\ClassContent;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class ClassContentMaterial extends BaseModel
{
    protected $guarded = ['id'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ClassContentLesson::class, 'lesson_id');
    }

    /** A short-lived link that resolves to the uploaded file (via Backblaze)
     *  or the plain pasted link, whichever this material has. Generate it
     *  fresh wherever a material is shown - never store or reuse one. */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute('class-content-material.download', now()->addMinutes(15), ['material' => $this->id]);
    }
}
