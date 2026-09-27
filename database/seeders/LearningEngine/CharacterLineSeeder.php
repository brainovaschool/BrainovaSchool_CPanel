<?php

namespace Database\Seeders\LearningEngine;

use App\Models\LearningEngine\CharacterLine;
use Illuminate\Database\Seeder;

/**
 * One-time move of Brainbot/Kea's dialogue out of config/characters.php
 * and into the database, so it becomes an editable Website Setup screen
 * instead of a file only a developer can touch. Matched on the exact line
 * text, so re-running this (every /db/migrate) never duplicates a row —
 * and never touches a line the school has since edited or deleted.
 */
class CharacterLineSeeder extends Seeder
{
    public function run(): void
    {
        $content = config('characters', []);

        $sortOrder = 0;
        foreach ($content as $character => $data) {
            foreach ($data['lines'] ?? [] as $context => $lines) {
                foreach ($lines as $line) {
                    CharacterLine::firstOrCreate([
                        'character' => $character,
                        'context'   => $context,
                        'line'      => $line,
                    ], [
                        'sort_order' => $sortOrder++,
                        'status'     => \App\Enums\Status::ACTIVE,
                    ]);
                }
            }
        }
    }
}
