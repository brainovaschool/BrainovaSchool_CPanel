<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class ReelScene extends Model
{
    protected $table = 'reel_scenes';
    protected $guarded = ['id'];

    protected $casts = [
        'generated_prompt_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(ReelProject::class, 'reel_project_id');
    }

    public function sceneCharacters()
    {
        return $this->hasMany(ReelSceneCharacter::class)->orderBy('sort_order');
    }
}
