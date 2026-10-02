<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;

class WatchLearnTab extends BaseModel
{
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function videos()
    {
        return $this->hasMany(WatchLearnVideo::class, 'tab_id');
    }
}
