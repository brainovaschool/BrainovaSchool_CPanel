<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class ProgramClassMaterial extends BaseModel
{
    protected $guarded = ['id'];

    public function programClass(): BelongsTo
    {
        return $this->belongsTo(ProgramClass::class, 'program_class_id');
    }

    /** Mirrors ClassContentMaterial::downloadUrl() - a short-lived link that
     *  resolves to the uploaded file (via Backblaze) or the plain pasted
     *  link, whichever this material has. */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute('program-class-material.download', now()->addMinutes(15), ['material' => $this->id]);
    }
}
