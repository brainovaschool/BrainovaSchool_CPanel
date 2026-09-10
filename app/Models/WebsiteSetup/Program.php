<?php

namespace App\Models\WebsiteSetup;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Program extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'overview'   => 'array',
        'highlights' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(ProgramCategory::class, 'program_category_id', 'id');
    }

    public function focus()
    {
        return $this->belongsTo(ProgramFocus::class, 'program_focus_id', 'id');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /** Resolved image path: uploaded file first, then external URL. */
    public function getImageAttribute()
    {
        if ($this->upload && $this->upload->path) {
            return globalAsset($this->upload->path, '1200X800.webp');
        }

        return $this->image_url ?: null;
    }
}
