<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;

class AvatarItem extends BaseModel
{
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
