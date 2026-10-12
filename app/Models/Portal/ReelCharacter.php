<?php

namespace App\Models\Portal;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;

class ReelCharacter extends Model
{
    protected $table = 'reel_characters';
    protected $guarded = ['id'];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function portrait()
    {
        return $this->belongsTo(Upload::class, 'portrait_upload_id');
    }
}
