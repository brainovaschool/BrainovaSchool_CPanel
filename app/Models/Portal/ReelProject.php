<?php

namespace App\Models\Portal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReelProject extends Model
{
    protected $table = 'reel_projects';
    protected $guarded = ['id'];

    protected $casts = [
        'character_ids' => 'array',
    ];

    public const FORMATS = [
        'vertical'   => 'Vertical (9:16)',
        'horizontal' => 'Horizontal (16:9)',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scenes()
    {
        return $this->hasMany(ReelScene::class)->orderBy('sort_order');
    }

    /** The characters marked as "involved in this reel" at project level —
     *  the roster AI scene generation is allowed to pick from. */
    public function involvedCharacters()
    {
        $ids = $this->character_ids ?: [];
        return ReelCharacter::whereIn('id', $ids)->orderBy('sort_order')->get();
    }
}
