<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;

/** One line of dialogue for Brainbot or Kea, tagged with the moment it's
 *  used in (welcome, encouragement, mistake_review, ...). Several rows can
 *  share the same character + context — Character::line() picks one at
 *  random, exactly like the config file this replaces used to. */
class CharacterLine extends BaseModel
{
    protected $guarded = ['id'];

    public const CHARACTERS = [
        'brainbot' => 'Brainbot',
        'kea'      => 'Kea',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function scopeFor($query, string $character, string $context)
    {
        return $query->where('character', $character)->where('context', $context);
    }
}
