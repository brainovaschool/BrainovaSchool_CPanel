<?php

namespace App\Models\WebsiteSetup;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Testimonial extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function getImageAttribute()
    {
        if ($this->upload && $this->upload->path) {
            return globalAsset($this->upload->path, '120X120.webp');
        }

        return $this->image_url ?: null;
    }
}
