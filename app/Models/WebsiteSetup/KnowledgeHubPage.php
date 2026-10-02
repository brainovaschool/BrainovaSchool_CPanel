<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeHubPage extends BaseModel
{
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function topics(): HasMany
    {
        return $this->hasMany(KnowledgeHubTopic::class, 'page_id')->orderBy('sort_order');
    }

    public function activeTopics(): HasMany
    {
        return $this->topics()->where('status', \App\Enums\Status::ACTIVE);
    }
}
