<?php

namespace App\Support;

/**
 * Thin accessor over config/characters.php so any feature can ask for a
 * Brainbot/Kea line by context without knowing the content lives in config.
 */
class Character
{
    public static function line(string $character, string $context): string
    {
        $lines = config("characters.{$character}.lines.{$context}", []);

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
