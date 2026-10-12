<?php

namespace App\Repositories\Portal;

use App\Models\Portal\ReelCharacter;
use App\Models\Portal\ReelProject;
use App\Models\Portal\ReelScene;
use App\Models\Portal\ReelSceneCharacter;
use App\Services\Ai\AiGateway;
use App\Services\Reel\ReelPromptBuilder;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Teacher-facing reel projects/scenes. Entirely separate from the ops
 *  Social Board's own `portal_reels` pipeline — no link between them. */
class ReelStudioRepository
{
    use ReturnFormatTrait;

    private const MAX_CHARACTERS_PER_SCENE = 3;
    private const MAX_SCENE_SECONDS        = 10; // Google Flow's hard per-scene limit
    private const MAX_AI_SCENES            = 20; // generous safety cap, well above what a target_duration_seconds up to a few minutes needs at 10s/scene

    public function projects(int $userId, bool $isManager)
    {
        return ReelProject::with('creator')
            ->when(!$isManager, fn ($q) => $q->where('created_by', $userId))
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?ReelProject
    {
        return ReelProject::with(['scenes.sceneCharacters.character'])->find($id);
    }

    /** Shared field extraction for store/update — keeps the two in sync. */
    private function projectFields($request): array
    {
        $characterIds = array_values(array_filter(array_map('intval', (array) $request->input('character_ids', []))));

        return [
            'title'                    => $request->title,
            'format'                   => in_array($request->format, ['vertical', 'horizontal'], true) ? $request->format : 'vertical',
            'hook'                     => $request->hook,
            'description'              => $request->description,
            'target_duration_seconds'  => $request->target_duration_seconds ?: null,
            'character_ids'            => $characterIds,
        ];
    }

    public function storeProject($request, int $userId): array
    {
        try {
            $project = ReelProject::create($this->projectFields($request) + ['created_by' => $userId]);

            return $this->responseWithSuccess('Reel project created.', ['id' => $project->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function updateProject($request, int $id): array
    {
        $project = ReelProject::find($id);
        if (!$project) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $project->fill($this->projectFields($request));
        $project->save();

        return $this->responseWithSuccess('Reel project updated.', []);
    }

    public function destroyProject(int $id): array
    {
        $project = ReelProject::find($id);
        if (!$project) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $project->delete(); // cascades to scenes -> scene_characters

        return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
    }

    /**
     * Replaces this project's entire scene list from the teacher's
     * `scenes_json` blob (one hidden field built client-side — see
     * reel-studio/show.blade.php). Upserts scenes/characters that are
     * still present, deletes whichever existed before but were removed
     * from the blob, all inside one transaction.
     */
    public function saveScenes($request, int $projectId): array
    {
        $decoded = json_decode((string) $request->input('scenes_json'), true);
        if (!is_array($decoded)) {
            return $this->responseWithError('No scene data was submitted.', []);
        }

        try {
            DB::transaction(function () use ($decoded, $projectId) {
                $keptSceneIds = [];

                foreach ($decoded as $index => $sceneData) {
                    if (!is_array($sceneData)) {
                        continue;
                    }

                    $sceneId = (int) ($sceneData['id'] ?? 0);
                    $scene   = $sceneId ? ReelScene::where('reel_project_id', $projectId)->find($sceneId) : null;
                    if (!$scene) {
                        $scene = new ReelScene(['reel_project_id' => $projectId]);
                    }

                    $scene->sort_order        = $index + 1;
                    $scene->duration_seconds  = max(1, min(self::MAX_SCENE_SECONDS, (int) ($sceneData['duration_seconds'] ?? 8)));
                    $scene->description       = (string) ($sceneData['description'] ?? '');
                    $scene->is_first_scene    = !empty($sceneData['is_first_scene']);
                    $scene->is_last_scene     = !empty($sceneData['is_last_scene']);
                    $scene->save();

                    $keptSceneIds[] = $scene->id;

                    $characters     = array_slice((array) ($sceneData['characters'] ?? []), 0, self::MAX_CHARACTERS_PER_SCENE);
                    $keptCharacterIds = [];

                    foreach ($characters as $cIndex => $charData) {
                        if (!is_array($charData) || empty($charData['character_id'])) {
                            continue;
                        }

                        $characterId = (int) $charData['character_id'];
                        $sceneChar   = ReelSceneCharacter::where('reel_scene_id', $scene->id)
                            ->where('reel_character_id', $characterId)
                            ->first() ?: new ReelSceneCharacter([
                                'reel_scene_id'      => $scene->id,
                                'reel_character_id'  => $characterId,
                            ]);

                        $sceneChar->sort_order    = $cIndex + 1;
                        $sceneChar->action_points = (string) ($charData['action_points'] ?? '');
                        $sceneChar->dialogue      = (string) ($charData['dialogue'] ?? '');
                        $sceneChar->save();

                        $keptCharacterIds[] = $characterId;
                    }

                    ReelSceneCharacter::where('reel_scene_id', $scene->id)
                        ->whereNotIn('reel_character_id', $keptCharacterIds ?: [0])
                        ->delete();
                }

                ReelScene::where('reel_project_id', $projectId)
                    ->whereNotIn('id', $keptSceneIds ?: [0])
                    ->delete();
            });

            return $this->responseWithSuccess('Scenes saved.', []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /**
     * AI-assisted scene generation. Builds a prompt from the project's own
     * title/hook/description/format/target length + its involved character
     * roster, asks the AI gateway for a full set of scenes, validates and
     * sanitizes the result, then APPENDS the new scenes after whatever
     * already exists (never replaces/deletes) — so a teacher's manually
     * built scenes are never at risk, even on a successful AI run. Nothing
     * is written at all if the AI call fails.
     */
    public function aiGenerateScenes(int $projectId, ?int $sceneCount = null): array
    {
        $project = ReelProject::find($projectId);
        if (!$project) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $characters = $project->involvedCharacters();

        $prompt = $this->buildAiScenePrompt($project, $characters, $sceneCount);
        $result = (new AiGateway())->generate($prompt, true);

        if (!$result['ok']) {
            return $this->responseWithError($result['message'], []);
        }

        $decoded = json_decode($result['text'], true);
        $scenes  = is_array($decoded['scenes'] ?? null) ? $decoded['scenes'] : null;

        if (!$scenes) {
            return $this->responseWithError('The AI did not return any usable scenes. Please try again.', []);
        }

        $allowedIds = $characters->pluck('id')->all();
        $clean      = $this->sanitizeAiScenes($scenes, $allowedIds);

        if (empty($clean)) {
            return $this->responseWithError('The AI response could not be read into valid scenes. Please try again.', []);
        }

        try {
            DB::transaction(function () use ($clean, $projectId) {
                $nextSortOrder = (int) ReelScene::where('reel_project_id', $projectId)->max('sort_order') + 1;

                foreach ($clean as $i => $sceneData) {
                    $scene = ReelScene::create([
                        'reel_project_id'  => $projectId,
                        'sort_order'       => $nextSortOrder + $i,
                        'duration_seconds' => $sceneData['duration_seconds'],
                        'description'      => $sceneData['description'],
                        'is_first_scene'   => $sceneData['is_first_scene'],
                        'is_last_scene'    => $sceneData['is_last_scene'],
                    ]);

                    foreach ($sceneData['characters'] as $cIndex => $charData) {
                        ReelSceneCharacter::create([
                            'reel_scene_id'      => $scene->id,
                            'reel_character_id'  => $charData['character_id'],
                            'sort_order'         => $cIndex + 1,
                            'action_points'      => $charData['action_points'],
                            'dialogue'           => $charData['dialogue'],
                        ]);
                    }
                }
            });

            return $this->responseWithSuccess(count($clean) . ' scene(s) generated — review and edit them below before generating prompts.', []);
        } catch (\Throwable $th) {
            Log::warning('Reel Studio AI scene save failed: ' . $th->getMessage());
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /**
     * Builds the AI instruction prompt for a full scene-generation run. If
     * the admin has written their own AI Scene Generation Prompt (Reel
     * Settings), that text is used as the creative/stylistic base — with
     * placeholders substituted in wherever it uses them. But correctness
     * must NEVER depend on the admin's template using the exact right
     * placeholder tokens: a bug caught in testing showed that when a
     * pasted template didn't reference {{title}}/{{hook}}/etc at all (e.g.
     * it used its own [TOPIC]/[NUMBER]-style placeholders instead), the
     * real title/hook/description/scene-count silently never reached the
     * AI at all, and it invented an unrelated topic. So those values are
     * now ALWAYS appended again afterward in a clearly-labeled block,
     * regardless of what the base template does or doesn't reference —
     * mirroring the same "ACTUAL INPUT ... ignore example placeholders
     * above" pattern already used by AiHelperRepository::inputBlock().
     * The system's own JSON-output contract is appended last, always,
     * since the response has to parse cleanly back into the editable
     * Scene Builder regardless of what the admin's prompt asks for.
     */
    private function buildAiScenePrompt(ReelProject $project, $characters, ?int $sceneCount = null): string
    {
        $roster = $characters->map(function (ReelCharacter $c) {
            return "- id={$c->id}, name=\"{$c->name}\": appearance: " . ($c->appearance_text ?: '(not set)')
                . ' | voice: ' . ($c->voice_profile_text ?: '(not set)')
                . ' | rules: ' . ($c->rules_text ?: '(not set)');
        })->implode("\n");

        $globalInstructions = trim((string) setting('reel_global_instructions'));
        $targetSeconds       = $project->target_duration_seconds ?: 40;
        $formatLabel         = ReelProject::FORMATS[$project->format] ?? ReelProject::FORMATS['vertical'];

        $suggestedSceneCount = max(1, (int) ceil($targetSeconds / self::MAX_SCENE_SECONDS));
        $sceneCountInstruction = $sceneCount
            ? "Create exactly {$sceneCount} scenes, each 3-10 seconds long."
            : "The TOTAL reel must add up to approximately {$targetSeconds} seconds. Each individual scene can only be 3-10 seconds long (Google Flow's hard limit), so use around {$suggestedSceneCount} scenes of close to 10 seconds each (or more, shorter scenes, if that tells the story better) — do NOT stop after just 2-3 scenes, the durations must add up to roughly {$targetSeconds} seconds in total.";

        $customTemplate = trim((string) setting('reel_ai_prompt_template'));

        // Common placeholder styles — both this app's own {{token}} form and
        // the [TOKEN] bracket form (matching the reference prompt this
        // feature was modeled on), so either style gets substituted inline
        // wherever the admin's template actually places it.
        $placeholders = [
            '{{title}}'                   => $project->title,
            '{{hook}}'                    => (string) $project->hook,
            '{{description}}'             => (string) $project->description,
            '{{format}}'                  => $formatLabel,
            '{{target_duration}}'         => (string) $targetSeconds,
            '{{scene_count_instruction}}' => $sceneCountInstruction,
            '{{characters}}'              => $roster,
            '[TOPIC]'                     => $project->title . ($project->description ? ' — ' . $project->description : ''),
            '[NUMBER]'                    => $sceneCount ? (string) $sceneCount : 'as many as needed',
            '[NUMBER OF SCENES]'          => $sceneCount ? (string) $sceneCount : 'as many as needed',
            '[DURATION(S)]'               => $sceneCount ? '10' : 'up to 10',
        ];

        $base = $customTemplate ? strtr($customTemplate, $placeholders) : <<<PROMPT
You are planning a short educational reel for a school, scene by scene, for use with Google Flow (an AI video generator). Google Flow can only generate a MAXIMUM of 10 seconds per scene, so every scene's duration must be between 3 and 10 seconds.

{$globalInstructions}

For each scene write: a short, visually rich scene description (setting, background, camera, lighting, visual effects, and how it transitions to the next scene), and for every character you include in that scene, a short specific action point and (optionally) a short line of dialogue in their established voice — leave dialogue empty for a character who doesn't speak in that scene. Make the dialogue feel like a real, engaging conversation, not a textbook explanation.
PROMPT;

        // Always present, regardless of the base template above — this is
        // what actually guarantees the AI sticks to the real topic/length.
        $actualInput = <<<PROMPT


---
ACTUAL REEL INFORMATION FOR THIS GENERATION — this is the real data for THIS reel; ignore any example topic, placeholder text, or sample scene count in the instructions above that doesn't match this:
Reel title: {$project->title}
What this reel is actually about: {$project->description}
Opening hook line (will be spoken by Kea in scene 1 automatically — do not write it yourself): {$project->hook}
Video format: {$formatLabel}
{$sceneCountInstruction}
Characters available for this reel (use ONLY these, by id — do not invent others):
{$roster}
PROMPT;

        $jsonContract = <<<PROMPT


---
Note: the system automatically adds the opening hook line and the fixed closing line ("Brainbot: Think Smart." / "Kea: Learn Smarter.") to the first and last scene — do not write those yourself, just make those scenes lead naturally into them.

Respond with ONLY valid JSON (no markdown, no commentary, no code fences) matching exactly this schema:
{"scenes":[{"description":"...","duration_seconds":8,"characters":[{"character_id":1,"action_points":"...","dialogue":"..."}]}]}
PROMPT;

        return $base . $actualInput . $jsonContract;
    }

    /** Validates/repairs the AI's scene list: clamps duration to Google
     *  Flow's 10s cap, drops any character id not in this project's own
     *  roster, caps characters per scene at 3, and always marks the first
     *  returned scene as the opening scene and the last as the closing
     *  scene (deterministic, not left to the AI to get right). */
    private function sanitizeAiScenes($scenes, array $allowedIds): array
    {
        $clean = [];

        foreach ($scenes as $sceneData) {
            if (!is_array($sceneData)) {
                continue;
            }

            $characters = array_slice((array) ($sceneData['characters'] ?? []), 0, self::MAX_CHARACTERS_PER_SCENE);
            $cleanChars = [];
            foreach ($characters as $charData) {
                if (!is_array($charData)) {
                    continue;
                }
                $characterId = (int) ($charData['character_id'] ?? 0);
                if (!in_array($characterId, $allowedIds, true)) {
                    continue;
                }
                $cleanChars[] = [
                    'character_id'  => $characterId,
                    'action_points' => (string) ($charData['action_points'] ?? ''),
                    'dialogue'      => (string) ($charData['dialogue'] ?? ''),
                ];
            }

            $clean[] = [
                'description'      => (string) ($sceneData['description'] ?? ''),
                'duration_seconds' => max(3, min(self::MAX_SCENE_SECONDS, (int) ($sceneData['duration_seconds'] ?? 8))),
                'is_first_scene'   => false,
                'is_last_scene'    => false,
                'characters'       => $cleanChars,
            ];

            if (count($clean) >= self::MAX_AI_SCENES) {
                break;
            }
        }

        if (!empty($clean)) {
            $clean[0]['is_first_scene']            = true;
            $clean[count($clean) - 1]['is_last_scene'] = true;
        }

        return $clean;
    }

    public function generatePrompt(int $sceneId): array
    {
        $scene = ReelScene::find($sceneId);
        if (!$scene) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $prompt = (new ReelPromptBuilder())->build($scene);

        $scene->generated_prompt    = $prompt;
        $scene->generated_prompt_at = now();
        $scene->save();

        return $this->responseWithSuccess('Prompt generated.', ['prompt' => $prompt]);
    }
}
