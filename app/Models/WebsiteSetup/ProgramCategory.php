<?php

namespace App\Models\WebsiteSetup;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProgramCategory extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function focuses()
    {
        return $this->hasMany(ProgramFocus::class)->orderBy('sort_order')->orderBy('name');
    }

    public function activeFocuses()
    {
        return $this->focuses()->where('status', 1);
    }

    public function programs()
    {
        return $this->hasMany(Program::class)->orderBy('sort_order')->orderBy('title');
    }

    public function activePrograms()
    {
        return $this->programs()->where('status', 1);
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id', 'id');
    }

    /** Resolved image path: uploaded file first, then external URL. */
    public function getImageAttribute()
    {
        if ($this->upload && $this->upload->path) {
            return globalAsset($this->upload->path, '1920X700.webp');
        }

        return $this->image_url ?: null;
    }
}
