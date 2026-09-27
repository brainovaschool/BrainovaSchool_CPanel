<?php

namespace App\Support;

use App\Models\LearningEngine\CharacterLine;

/**
 * Accessor for a Brainbot/Kea line by context. Lines themselves live in
 * the character_lines table now — editable from Website Setup — not in
 * this file. config/characters.php is kept only as a fallback for a
 * context nobody has added a database line for yet (and for name/image,
 * which aren't part of the admin screen).
 */
class Character
{
    public static function line(string $character, string $context): string
    {
        try {
            $lines = CharacterLine::active()->for($character, $context)->pluck('line')->all();
        } catch (\Throwable $e) {
            $lines = [];
        }

        if (empty($lines)) {
            $lines = config("characters.{$character}.lines.{$context}", []);
        }

        if (empty($lines)) {
            return '';
        }

        return $lines[array_rand($lines)];
    }

    public static function name(string $character): string
    {
        return config("characters.{$character}.name", ucfirst($character));
    }

    public static function image(string $character): string
    {
        return config("characters.{$character}.image", '');
    }
}
