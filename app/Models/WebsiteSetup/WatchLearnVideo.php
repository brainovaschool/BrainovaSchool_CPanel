<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use App\Traits\ResolvesVideoEmbed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchLearnVideo extends BaseModel
{
    use ResolvesVideoEmbed;

    protected $guarded = ['id'];

    public const ORIENTATIONS = [
        'landscape' => 'Landscape (wide)',
        'portrait'  => 'Portrait (tall)',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function scopeLandscape($query)
    {
        return $query->where('orientation', 'landscape');
    }

    public function scopePortrait($query)
    {
        return $query->where('orientation', 'portrait');
    }

    public function tab(): BelongsTo
    {
        return $this->belongsTo(WatchLearnTab::class, 'tab_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WatchLearnTemplate::class, 'template_id');
    }
}
