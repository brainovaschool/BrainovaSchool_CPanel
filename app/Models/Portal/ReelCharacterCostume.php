<?php

namespace App\Models\Portal;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Model;

class ReelCharacterCostume extends Model
{
    protected $table = 'reel_character_costumes';
    protected $guarded = ['id'];

    public function character()
    {
        return $this->belongsTo(ReelCharacter::class, 'reel_character_id');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }
}
