<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;

class AvatarItem extends BaseModel
{
    protected $guarded = ['id'];

    /** The four layers a student's look is built from, bottom to top.
     *  'avatar' is the base character — every student always has exactly
     *  one; the rest are optional overlays. 'accessory' is the only one a
     *  student can wear several of at once. */
    public const CATEGORIES = [
        'avatar'    => 'Base Character',
        'outfit'    => 'Outfit',
        'hat'       => 'Hat',
        'accessory' => 'Accessory',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
