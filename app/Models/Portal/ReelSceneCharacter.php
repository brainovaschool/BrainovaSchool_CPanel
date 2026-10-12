<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class ReelSceneCharacter extends Model
{
    protected $table = 'reel_scene_characters';
    protected $guarded = ['id'];

    public function scene()
    {
        return $this->belongsTo(ReelScene::class, 'reel_scene_id');
    }

    public function character()
    {
        return $this->belongsTo(ReelCharacter::class, 'reel_character_id');
    }
}
