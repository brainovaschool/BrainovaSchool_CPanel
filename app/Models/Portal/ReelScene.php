<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class ReelScene extends Model
{
    protected $table = 'reel_scenes';
    protected $guarded = ['id'];

    public const TYPE_START   = 'start';
    public const TYPE_CONTENT = 'content';
    public const TYPE_END     = 'end';

    protected $casts = [
        'generated_prompt_at' => 'datetime',
        'is_first_scene'      => 'boolean',
        'is_last_scene'       => 'boolean',
        'character_ids'       => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(ReelProject::class, 'reel_project_id');
    }

    public function sceneCharacters()
    {
        return $this->hasMany(ReelSceneCharacter::class)->orderBy('sort_order');
    }

    /** The 1-3 ReelCharacter models actually appearing in this scene, in
     *  the order they were checked. */
    public function characters()
    {
        $ids = $this->character_ids ?: [];
        return ReelCharacter::whereIn('id', $ids)->get()->sortBy(fn ($c) => array_search($c->id, $ids))->values();
    }
}
