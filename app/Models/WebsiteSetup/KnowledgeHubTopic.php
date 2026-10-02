<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeHubTopic extends BaseModel
{
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(KnowledgeHubPage::class, 'page_id');
    }
}
