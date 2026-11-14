<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramClassOutcome extends BaseModel
{
    protected $guarded = ['id'];

    public function programClass(): BelongsTo
    {
        return $this->belongsTo(ProgramClass::class, 'program_class_id');
    }
}
