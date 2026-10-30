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
 * Every reel is always bracketed by a Start scene (the intro, closing with
 * the reel's Hook) and an End scene (the send-off, closing with its End
 * Hook custom field), either side of the numbered content scenes. Both
 * reminders are spelled out as instructions in the document rather than
 * rewritten into the teacher's own Dialogue text, so nothing they typed is
 * silently changed.
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

        $hook    = trim((string) $project->hook) !== '' ? trim($project->hook) : null;
        $endHook = $this->endHookText($project);

        if ($hook) {
            $lines[] = 'OPENING HOOK (closes the Start scene, word for word): ' . $hook;
            $lines[] = '';
        }
        if ($endHook) {
            $lines[] = 'END HOOK (closes the End scene, word for word): ' . $endHook;
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
            $lines = array_merge($lines, $this->sceneLines($scene, $sceneLength, $hook, $endHook));
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
        $hook        = trim((string) $project->hook) !== '' ? trim($project->hook) : null;
        $endHook     = $this->endHookText($project);
        $lines       = array_merge($lines, $this->sceneLines($scene, $sceneLength, $hook, $endHook));

        return implode("\n", $lines);
    }

    /** The lines for one scene: header (Start/Scene N/End, plus length),
     *  title, description, dialogue, and the consistency details for
     *  whichever 1-3 characters are checked into it. Shared by the
     *  whole-reel document and the single-scene download so both describe
     *  a scene identically. $hook/$endHook: shown as a reminder only on
     *  the Start/End scenes respectively - never rewritten into the
     *  teacher's own Dialogue text. */
    private function sceneLines(ReelScene $scene, int $sceneLength, ?string $hook = null, ?string $endHook = null): array
    {
        $lines = [];

        if ($scene->scene_type === ReelScene::TYPE_START) {
            $header = "START SCENE — {$sceneLength}s (intro — what this video is about)";
        } elseif ($scene->scene_type === ReelScene::TYPE_END) {
            $header = "END SCENE — {$sceneLength}s (send-off)";
        } else {
            $header = "SCENE {$scene->sort_order} — {$sceneLength}s";
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
        if ($scene->scene_type === ReelScene::TYPE_START && $hook) {
            $lines[] = '';
            $lines[] = "(This scene must close with the Opening Hook, word for word: \"{$hook}\")";
        }
        if ($scene->scene_type === ReelScene::TYPE_END) {
            $lines[] = '';
            $lines[] = $endHook
                ? "(This scene must close with the End Hook, word for word: \"{$endHook}\")"
                : '(This scene is the send-off — closing line.)';
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

    /** Same source/lookup as pillarAndSeries() above, for the "End Hook"
     *  custom field - spoken word for word in the End scene. */
    private function endHookText(ReelProject $project): ?string
    {
        $reel   = $project->portalReel;
        $fields = $reel ? ($reel->custom_fields ?: []) : ($project->custom_fields ?: []);

        $definitions = PortalReelCustomField::orderBy('sort_order')->get();
        $key = optional($definitions->first(fn ($f) => strtolower(trim($f->label)) === 'end hook'))->key;

        return $key ? ($fields[$key] ?? null) : null;
    }
}
