<?php

namespace App\Services\Reel;

use App\Models\Portal\ReelProject;
use App\Models\Portal\ReelScene;

/**
 * Assembles one scene's complete, self-contained Google Flow prompt purely
 * from stored data (the admin's character profiles/global instructions +
 * the teacher's own scene text) — no AI call, no network request, so a
 * scene prompt can always be generated even if every AI provider is down
 * or none is configured at all.
 *
 * Two fixed brand rules are applied mechanically here, regardless of what
 * the teacher (or AI) typed into the scene: a scene marked "first scene"
 * always opens with Kea speaking the reel's hook, and a scene marked "last
 * scene" always closes with Brainbot saying "Think Smart" and Kea saying
 * "Learn Smarter" — the closing tagline is added even if those two
 * characters weren't picked for that scene (with a reminder note so the
 * teacher notices and can add them for visual consistency).
 */
class ReelPromptBuilder
{
    private const CLOSING_LINES = [
        'brainbot' => 'Think Smart.',
        'kea'      => 'Learn Smarter.',
    ];

    public function build(ReelScene $scene): string
    {
        $project         = $scene->project;
        $sceneCharacters = $scene->sceneCharacters()->with('character')->orderBy('sort_order')->get();
        $lines           = [];

        $header = "SCENE {$scene->sort_order} — {$scene->duration_seconds} seconds";
        if ($scene->is_first_scene) {
            $header .= ' (OPENING SCENE — HOOK)';
        }
        if ($scene->is_last_scene) {
            $header .= ' (CLOSING SCENE)';
        }
        $lines[] = $header;
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
        }

        $dialogueByName = $this->buildDialogueLines($scene, $project, $sceneCharacters);
        if (!empty($dialogueByName)) {
            $lines[] = 'DIALOGUE:';
            foreach ($dialogueByName as $name => $line) {
                $lines[] = "{$name}: {$line}";
            }
            $lines[] = '';
        }

        $lines[] = 'CONTINUITY REQUIREMENTS:';
        $lines[] = 'Keep every character\'s identity, appearance, clothing, proportions and visual style exactly consistent with their reference image and with every other scene in this reel.';
        $lines[] = '';

        $lines[] = 'GENERATION CONSTRAINTS:';
        $formatLabel = $project ? (ReelProject::FORMATS[$project->format] ?? ReelProject::FORMATS['vertical']) : ReelProject::FORMATS['vertical'];
        $lines[] = "Duration: exactly {$scene->duration_seconds} seconds (Google Flow's hard limit is 10 seconds per scene). {$formatLabel} format.";
        $lines[] = 'This prompt is fully self-contained — do not reference any other scene, document, or prior instruction not included above.';

        return implode("\n", $lines);
    }

    /**
     * Normal per-character dialogue (in scene order), with the hook
     * mechanically merged onto the start of Kea's line on the first scene,
     * and the fixed closing tagline mechanically appended on the last
     * scene — applied here so they're guaranteed correct regardless of
     * what was typed (manually or by AI) into the scene's own fields.
     */
    private function buildDialogueLines(ReelScene $scene, ?ReelProject $project, $sceneCharacters): array
    {
        $byName = [];
        foreach ($sceneCharacters as $sc) {
            if (!$sc->character || trim((string) $sc->dialogue) === '') {
                continue;
            }
            $byName[$sc->character->name] = trim($sc->dialogue);
        }

        if ($scene->is_first_scene) {
            $hook = trim((string) ($project->hook ?? ''));
            if ($hook) {
                $keaName = $this->findNameLike($byName, $sceneCharacters, 'kea');
                $keaName = $keaName ?: 'Kea';
                $byName = $this->prepend($byName, $keaName, $hook);
            }
        }

        if ($scene->is_last_scene) {
            foreach (self::CLOSING_LINES as $search => $closingLine) {
                $name = $this->findNameLike($byName, $sceneCharacters, $search) ?: ucfirst($search);
                $existing = $byName[$name] ?? '';
                $byName[$name] = trim($existing . ' ' . $closingLine);
            }
        }

        return $byName;
    }

    /** Matches an existing dialogue-map key or a scene character's name
     *  case-insensitively against $needle (e.g. "kea"), so "Kea"/"kea"/"KEA"
     *  all resolve to whatever the admin actually typed as the name. */
    private function findNameLike(array $byName, $sceneCharacters, string $needle): ?string
    {
        foreach (array_keys($byName) as $name) {
            if (strcasecmp($name, $needle) === 0) {
                return $name;
            }
        }
        foreach ($sceneCharacters as $sc) {
            if ($sc->character && strcasecmp($sc->character->name, $needle) === 0) {
                return $sc->character->name;
            }
        }
        return null;
    }

    private function prepend(array $byName, string $name, string $text): array
    {
        $existing = $byName[$name] ?? '';
        unset($byName[$name]);
        $merged = trim($text . ' ' . $existing);
        return [$name => $merged] + $byName;
    }
}
