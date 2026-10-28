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

    /** An optional uploaded reference doc (PDF, doc, image, etc.) - a
     *  supplement a designer/writer can attach, read by people, not fed
     *  into the AI prompt (which uses the text fields below instead). */
    public function descriptionUpload()
    {
        return $this->belongsTo(Upload::class, 'description_upload_id');
    }

    /** As many costume/outfit reference images as needed, each separately
     *  downloadable. */
    public function costumes()
    {
        return $this->hasMany(ReelCharacterCostume::class)->orderBy('sort_order');
    }
}
