<?php

namespace App\Services\Reel;

use App\Models\Portal\ReelScene;

/**
 * Assembles one scene's complete, self-contained Google Flow prompt purely
 * from stored data (the admin's character profiles/global instructions +
 * the teacher's own scene text) — no AI call, no network request, so a
 * scene prompt can always be generated even if every AI provider is down
 * or none is configured at all.
 */
class ReelPromptBuilder
{
    public function build(ReelScene $scene): string
    {
        $sceneCharacters = $scene->sceneCharacters()->with('character')->get();
        $lines = [];

        $lines[] = "SCENE {$scene->sort_order} — {$scene->duration_seconds} seconds";
        $lines[] = '';

        $globalInstructions = trim((string) setting('reel_global_instructions'));
        if ($globalInstructions) {
            $lines[] = 'GLOBAL REEL INSTRUCTIONS (apply to every scene):';
            $lines[] = $globalInstructions;
            $lines[] = '';
        }

        if ($sceneCharacters->isEmpty()) {
            $lines[] = 'BACKGROUND-ONLY SHOT — no characters appear in this scene.';
            $lines[] = '';
        } else {
            $lines[] = 'CHARACTERS IN THIS SCENE:';
            foreach ($sceneCharacters as $sc) {
                $character = $sc->character;
                if (!$character) {
                    continue;
                }

                $lines[] = '';
                $lines[] = "— {$character->name} —";
                if ($character->portrait_upload_id) {
                    $lines[] = '(Reference image attached for this character — use it for visual consistency.)';
                }
                if ($character->appearance_text) {
                    $lines[] = 'Visual appearance & consistency: ' . $character->appearance_text;
                }
                if (trim((string) $sc->dialogue) !== '' && $character->voice_profile_text) {
                    $lines[] = 'Voice profile: ' . $character->voice_profile_text;
                }
                if ($character->rules_text) {
                    $lines[] = 'Character rules: ' . $character->rules_text;
                }
            }
            $lines[] = '';
        }

        $lines[] = 'SCENE DESCRIPTION (setting, background, environment, camera, visual events):';
        $lines[] = $scene->description ?: '(none provided)';
        $lines[] = '';

        if ($sceneCharacters->isNotEmpty()) {
            $lines[] = 'CHARACTER ACTION POINTS:';
            foreach ($sceneCharacters as $sc) {
                if (!$sc->character) {
                    continue;
                }
                $lines[] = "What {$sc->character->name} does: " . ($sc->action_points ?: '(no specific action given)');
            }
            $lines[] = '';

            $hasDialogue = $sceneCharacters->contains(fn ($sc) => trim((string) $sc->dialogue) !== '');
            if ($hasDialogue) {
                $lines[] = 'DIALOGUE:';
                foreach ($sceneCharacters as $sc) {
                    if (!$sc->character || trim((string) $sc->dialogue) === '') {
                        continue;
                    }
                    $lines[] = "{$sc->character->name}: {$sc->dialogue}";
                }
                $lines[] = '';
            }
        }

        $lines[] = 'CONTINUITY REQUIREMENTS:';
        $lines[] = 'Keep every character\'s identity, appearance, clothing, proportions and visual style exactly consistent with their reference image and with every other scene in this reel.';
        $lines[] = '';

        $lines[] = 'GENERATION CONSTRAINTS:';
        $lines[] = "Duration: exactly {$scene->duration_seconds} seconds. Vertical 9:16 format.";
        $lines[] = 'This prompt is fully self-contained — do not reference any other scene, document, or prior instruction not included above.';

        return implode("\n", $lines);
    }
}
