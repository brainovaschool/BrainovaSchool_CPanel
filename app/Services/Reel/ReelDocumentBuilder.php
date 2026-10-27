<?php

namespace App\Services\Reel;

use App\Models\Portal\PortalReelCustomField;
use App\Models\Portal\ReelProject;

/**
 * Assembles one whole-reel script/prompt document from a project's own
 * setup (title/hook/description/format/characters) plus every saved scene's
 * Description and Dialogues - no AI call, no network request, so a script
 * can always be produced even if every AI provider is down or none is
 * configured at all.
 *
 * Two fixed brand rules are applied mechanically here, regardless of what
 * was typed (manually or by AI) into any scene: scene 1 opens with Kea
 * speaking the reel's hook, and the last scene closes with Brainbot saying
 * "Think Smart" and Kea saying "Learn Smarter" - spelled out as instructions
 * rather than rewritten into the teacher's own Dialogue text, so nothing
 * they typed is silently changed.
 */
class ReelDocumentBuilder
{
    public function build(ReelProject $project): string
    {
        $lines = [];

        $lines[] = 'REEL: ' . $project->title;
        $lines[] = 'FORMAT: ' . (ReelProject::FORMATS[$project->format] ?? ReelProject::FORMATS['vertical']);

        [$pillar, $series] = $this->pillarAndSeries($project);
        $lines[] = 'PILLAR: ' . ($pillar ?: '—');
        $lines[] = 'SERIES: ' . ($series ?: '—');

        $sceneLength = $project->scene_length_seconds ?: 8;
        $count       = $project->scenes->count();
        $lines[] = "LENGTH: {$project->target_duration_seconds}s target across {$count} scene(s) of {$sceneLength}s each";
        $lines[] = '';

        if (trim((string) $project->hook) !== '') {
            $lines[] = 'HOOK (opening line, spoken by Kea in Scene 1, word for word — do not repeat it in any scene\'s dialogue): ' . trim($project->hook);
            $lines[] = '';
        }

        if (trim((string) $project->description) !== '') {
            $lines[] = 'DESCRIPTION: ' . trim($project->description);
            $lines[] = '';
        }

        $globalInstructions = trim((string) setting('reel_global_instructions'));
        if ($globalInstructions) {
            $lines[] = 'GLOBAL REEL INSTRUCTIONS (apply to every scene):';
            $lines[] = $globalInstructions;
            $lines[] = '';
        }

        $characters = $project->involvedCharacters();
        if ($characters->isNotEmpty()) {
            $lines[] = 'CHARACTERS INVOLVED:';
            foreach ($characters as $character) {
                $lines[] = "- {$character->name}: appearance: " . ($character->appearance_text ?: '(not set)')
                    . ' | voice: ' . ($character->voice_profile_text ?: '(not set)')
                    . ' | rules: ' . ($character->rules_text ?: '(not set)');
            }
            $lines[] = '';
        }

        $lines[] = str_repeat('-', 40);

        $scenes = $project->scenes;
        $last   = $scenes->count() - 1;

        foreach ($scenes as $index => $scene) {
            $lines[] = '';
            $header = "SCENE {$scene->sort_order} — {$sceneLength}s";
            if ($index === 0) {
                $header .= ' (OPENING SCENE)';
            }
            if ($index === $last) {
                $header .= ' (CLOSING SCENE)';
            }
            $lines[] = $header;
            $lines[] = '';
            $lines[] = 'DESCRIPTION:';
            $lines[] = $scene->description ?: '(none provided)';
            $lines[] = '';
            $lines[] = 'DIALOGUE:';
            $lines[] = $scene->dialogue ?: '(none provided)';
            if ($index === $last) {
                $lines[] = '';
                $lines[] = '(This scene must also close with: Brainbot: Think Smart. / Kea: Learn Smarter. — added automatically if not already written above.)';
            }
            $lines[] = '';
            $lines[] = str_repeat('-', 40);
        }

        $lines[] = '';
        $lines[] = 'CONTINUITY: keep every character\'s identity, appearance, clothing, proportions and visual style exactly consistent with their reference image and with every other scene in this reel.';

        return implode("\n", $lines);
    }

    /** Pillar/Series come from the suggested Reel when this project was
     *  opened from one (the admin-owned, authoritative copy); otherwise
     *  from this project's own custom fields (a standalone project has no
     *  other home for them). */
    private function pillarAndSeries(ReelProject $project): array
    {
        $reel = $project->portalReel;
        if ($reel) {
            return [$reel->category, $reel->series_name];
        }

        $fields     = $project->custom_fields ?: [];
        $definitions = PortalReelCustomField::orderBy('sort_order')->get();
        $pillarKey  = optional($definitions->first(fn ($f) => strtolower(trim($f->label)) === 'pillar'))->key;
        $seriesKey  = optional($definitions->first(fn ($f) => strtolower(trim($f->label)) === 'series'))->key;

        return [$pillarKey ? ($fields[$pillarKey] ?? null) : null, $seriesKey ? ($fields[$seriesKey] ?? null) : null];
    }
}
