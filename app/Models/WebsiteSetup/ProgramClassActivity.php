<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class ProgramClassActivity extends BaseModel
{
    protected $guarded = ['id'];

    public function programClass(): BelongsTo
    {
        return $this->belongsTo(ProgramClass::class, 'program_class_id');
    }

    /** Same shape as ProgramClassMaterial::downloadUrl() - a short-lived
     *  link that resolves to the uploaded file (via Backblaze) or the
     *  plain pasted link_url, whichever this activity has. */
    public function downloadUrl(): string
    {
        return URL::temporarySignedRoute('program-class-activity.download', now()->addMinutes(15), ['activity' => $this->id]);
    }
}
