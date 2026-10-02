<?php

namespace App\Models\WebsiteSetup;

use App\Models\BaseModel;
use App\Models\Upload;

class WatchLearnTemplate extends BaseModel
{
    protected $guarded = ['id'];

    public const ORIENTATIONS = [
        'landscape' => 'Landscape (wide)',
        'portrait'  => 'Portrait (tall)',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }
}
