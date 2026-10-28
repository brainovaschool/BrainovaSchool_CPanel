<?php

namespace App\Services\Reel;

use App\Models\Portal\PortalReelCustomField;
use App\Models\Portal\ReelProject;
use App\Models\Portal\ReelScene;

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
            $lines[] = 'CHARACTERS INVOLVED IN THIS REEL: ' . $characters->pluck('name')->implode(', ');
            $lines[] = '(Full appearance/voice/rules for each one appear under the specific scene(s) where they are checked in, below.)';
            $lines[] = '';
        }

        $lines[] = str_repeat('-', 40);

        foreach ($project->scenes as $scene) {
            $lines[] = '';
            $lines = array_merge($lines, $this->sceneLines($scene, $sceneLength));
            $lines[] = '';
            $lines[] = str_repeat('-', 40);
        }

        $lines[] = '';
        $lines[] = 'CONTINUITY: keep every character\'s identity, appearance, clothing, proportions and visual style exactly consistent with their reference image and with every other scene in this reel.';

        return implode("\n", $lines);
    }

    /**
     * One scene's own script, standalone and self-contained — the project's
     * title/pillar/series/length, the Universal Clip Settings (admin's own
     * Global Instructions, repeated here since this file travels alone),
     * then that scene's title/number/length/description/dialogue/character
     * consistency, in that order.
     */
    public function buildScene(ReelProject $project, ReelScene $scene): string
    {
        $lines = [];

        $lines[] = 'REEL: ' . $project->title;
        [$pillar, $series] = $this->pillarAndSeries($project);
        $lines[] = 'PILLAR: ' . ($pillar ?: '—');
        $lines[] = 'SERIES: ' . ($series ?: '—');
        $lines[] = '';

        $globalInstructions = trim((string) setting('reel_global_instructions'));
        $lines[] = 'UNIVERSAL CLIP SETTINGS (applies to every scene in this reel, set in Social Board Settings):';
        $lines[] = $globalInstructions ?: '(none set)';
        $lines[] = '';
        $lines[] = str_repeat('-', 40);
        $lines[] = '';

        $sceneLength = $project->scene_length_seconds ?: $scene->duration_seconds ?: 8;
        $lines       = array_merge($lines, $this->sceneLines($scene, $sceneLength));

        return implode("\n", $lines);
    }

    /** The lines for one scene: header (number/length/opening-closing tag),
     *  title, description, dialogue, and the consistency details for
     *  whichever 1-3 characters are checked into it. Shared by the
     *  whole-reel document and the single-scene download so both describe
     *  a scene identically. */
    private function sceneLines(ReelScene $scene, int $sceneLength): array
    {
        $lines = [];

        $header = "SCENE {$scene->sort_order} — {$sceneLength}s";
        if ($scene->is_first_scene) {
            $header .= ' (OPENING SCENE)';
        }
        if ($scene->is_last_scene) {
            $header .= ' (CLOSING SCENE)';
        }
        $lines[] = $header;
        if (trim((string) $scene->title) !== '') {
            $lines[] = 'TITLE: ' . trim($scene->title);
        }
        $lines[] = '';
        $lines[] = 'DESCRIPTION:';
        $lines[] = $scene->description ?: '(none provided)';
        $lines[] = '';
        $lines[] = 'DIALOGUE:';
        $lines[] = $scene->dialogue ?: '(none provided)';
        if ($scene->is_last_scene) {
            $lines[] = '';
            $lines[] = '(This scene must also close with: Brainbot: Think Smart. / Kea: Learn Smarter. — added automatically if not already written above.)';
        }

        $sceneCharacters = $scene->characters();
        if ($sceneCharacters->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'CHARACTERS IN THIS SCENE:';
            foreach ($sceneCharacters as $character) {
                $lines[] = "- {$character->name}: appearance: " . ($character->appearance_text ?: '(not set)')
                    . ' | voice: ' . ($character->voice_profile_text ?: '(not set)')
                    . ' | rules: ' . ($character->rules_text ?: '(not set)');
                if ($character->portrait) {
                    $lines[] = '  Reference portrait on file — use it for visual consistency.';
                }
                if ($character->costumes->isNotEmpty()) {
                    $lines[] = '  Costume references on file: ' . $character->costumes->pluck('label')->map(fn ($l) => $l ?: 'untitled')->implode(', ');
                }
            }
        }

        return $lines;
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
