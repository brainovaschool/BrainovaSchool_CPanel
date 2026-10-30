<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalReel;
use App\Models\Portal\PortalReelCustomField;
use App\Models\Portal\ReelCharacter;
use App\Models\Portal\ReelProject;
use App\Models\Portal\ReelScene;
use App\Repositories\WebsiteSetup\AiHelperRepository;
use App\Services\Ai\AiGateway;
use App\Services\Portal\PortalNotifier;
use App\Services\Reel\ReelDocumentBuilder;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/** Teacher-facing reel projects/scenes. Entirely separate from the ops
 *  Social Board's own `portal_reels` pipeline — no link between them. */
class ReelStudioRepository
{
    use ReturnFormatTrait, CommonHelperTrait;

    private const MAX_SCENE_SECONDS = 10; // Google Flow's hard per-scene limit

    private $aiHelperRepo;

    public function __construct(AiHelperRepository $aiHelperRepo)
    {
        $this->aiHelperRepo = $aiHelperRepo;
    }

    /** Only standalone projects (never linked to a Reels/Content Pool
     *  suggestion) - a linked one already shows on the Reels page itself
     *  and opens straight into its project from there, so listing it
     *  again here would just be the same reel twice. */
    public function projects(int $userId, bool $isManager)
    {
        return ReelProject::with('creator')
            ->whereDoesntHave('portalReel')
            ->when(!$isManager, fn ($q) => $q->where('created_by', $userId))
            ->orderByDesc('id')
            ->get();
    }

    public function find(int $id): ?ReelProject
    {
        return ReelProject::with(['scenes', 'portalReel'])->find($id);
    }

    /** Shared with the mobile API so both sides agree on who can open/edit
     *  a project: whoever created it, portal_manage, or -- if it's linked
     *  to a Reels/Content Pool suggestion -- whoever that reel is assigned
     *  to. Moved here (was private on ReelStudioController) to avoid drift. */
    public function authorizeProject(?ReelProject $project, int $userId, ?int $actingStaffId, bool $isManager): bool
    {
        if (!$project) {
            return false;
        }
        if ($isManager || (int) $project->created_by === $userId) {
            return true;
        }
        $reel = $project->portalReel;
        if ($reel && $reel->assigned_to) {
            return $actingStaffId !== null && $actingStaffId === (int) $reel->assigned_to;
        }
        return false;
    }

    public function customFields()
    {
        return PortalReelCustomField::orderBy('sort_order')->get();
    }

    /** Shared field extraction for store/update — keeps the two in sync. */
    private function projectFields($request): array
    {
        $characterIds = array_values(array_filter(array_map('intval', (array) $request->input('character_ids', []))));

        $sceneLength = $request->scene_length_seconds
            ? max(1, min(self::MAX_SCENE_SECONDS, (int) $request->scene_length_seconds))
            : null;

        return [
            'title'                    => $request->title,
            'format'                   => in_array($request->format, ['vertical', 'horizontal'], true) ? $request->format : 'vertical',
            'hook'                     => $request->hook,
            'description'              => $request->description,
            'target_duration_seconds'  => $request->target_duration_seconds ?: null,
            'scene_length_seconds'     => $sceneLength,
            'character_ids'            => $characterIds,
            'canva_link'               => $request->canva_link ?: null,
            'drive_link'               => $request->drive_link ?: null,
        ];
    }

    /** $existing: a File-type custom field with no new upload on this
     *  submit keeps whatever it already had instead of being wiped. */
    private function collectCustomFieldValues($request, array $existing = []): array
    {
        $submitted = (array) $request->input('custom_fields', []);
        $values    = [];

        foreach ($this->customFields() as $field) {
            if ($field->type === PortalReelCustomField::TYPE_CHECKBOX) {
                $values[$field->key] = isset($submitted[$field->key]);
                continue;
            }
            if ($field->type === PortalReelCustomField::TYPE_FILE) {
                if ($request->hasFile("custom_fields.{$field->key}")) {
                    $values[$field->key] = $this->UploadImageCreate($request->file("custom_fields.{$field->key}"), 'uploads/reel-projects');
                } elseif (isset($existing[$field->key])) {
                    $values[$field->key] = $existing[$field->key];
                }
                continue;
            }
            if ($field->list_key === PortalReelCustomField::SOURCE_REEL_CHARACTERS) {
                $arr = array_values(array_filter((array) ($submitted[$field->key] ?? [])));
                if ($arr) {
                    $values[$field->key] = $arr;
                }
                continue;
            }
            $v = trim((string) ($submitted[$field->key] ?? ''));
            if ($v !== '') {
                $values[$field->key] = $v;
            }
        }

        return $values;
    }

    public function storeProject($request, int $userId): array
    {
        try {
            $fields = $this->projectFields($request);
            $fields['custom_fields'] = $this->collectCustomFieldValues($request);
            if ($request->hasFile('cover_file')) {
                $fields['cover_upload_id'] = $this->UploadImageCreate($request->file('cover_file'), 'uploads/reel-projects');
            }

            $project = ReelProject::create($fields + ['created_by' => $userId]);

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
        $project->custom_fields = $this->collectCustomFieldValues($request, $project->custom_fields ?: []);
        if ($request->hasFile('cover_file')) {
            $project->cover_upload_id = $this->UploadImageCreate($request->file('cover_file'), 'uploads/reel-projects');
        }
        $project->save();

        $this->propagateDriveLink($project);

        return $this->responseWithSuccess('Reel project updated.', []);
    }

    /** The Drive link lives on this project, but the Reels list, the
     *  Production/Upload Plan calendars and anywhere else this reel is
     *  shown all read portal_reels.drive_link directly - so when this
     *  project is linked to a suggested Reel, copy it there too. Canva
     *  link stays on the project only, nothing else reads it. */
    private function propagateDriveLink(ReelProject $project): void
    {
        $reel = $project->portalReel;
        if ($reel && $reel->drive_link !== $project->drive_link) {
            $reel->drive_link = $project->drive_link;
            $reel->save();
        }
    }

    /** Explicit, teacher-triggered reset (a confirm() in the browser warns
     *  them first): throws away whatever scenes currently exist and starts
     *  over with a fresh, blank set sized to the project's current target/
     *  scene length. Never runs on its own - only from this action. */
    public function createScenesFromZero(int $projectId): array
    {
        $project = ReelProject::find($projectId);
        if (!$project) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $count = $this->sceneCount($project);
        if (!$count) {
            return $this->responseWithError('Set a target total length and a scene length in Reel Setup first.', []);
        }

        ReelScene::where('reel_project_id', $project->id)->delete(); // cascades to any reel_scene_characters rows

        for ($i = 0; $i < $count; $i++) {
            ReelScene::create([
                'reel_project_id'  => $project->id,
                'sort_order'       => $i + 1,
                'duration_seconds' => $project->scene_length_seconds ?: 8,
                'title'            => '',
                'description'      => '',
                'dialogue'         => '',
                'character_ids'    => [],
                'is_first_scene'   => $i === 0,
                'is_last_scene'    => $i === $count - 1,
            ]);
        }

        return $this->responseWithSuccess($count . ' blank scene(s) created.', []);
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
     * reel-studio/show.blade.php): just a Description and Dialogue per
     * scene now. Duration is uniform (the project's own scene length) and
     * first/last scene are derived from position, not stored as separate
     * flags the teacher has to set correctly. Upserts scenes still present,
     * deletes whichever existed before but were removed from the blob, all
     * inside one transaction. Returns the compiled script document on
     * success so the caller can stream it straight back as a download.
     */
    public function saveScenes($request, int $projectId): array
    {
        $project = ReelProject::find($projectId);
        if (!$project) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $decoded = json_decode((string) $request->input('scenes_json'), true);
        if (!is_array($decoded) || empty($decoded)) {
            return $this->responseWithError('No scene data was submitted.', []);
        }

        $sceneLength = $project->scene_length_seconds ?: 8;
        $count       = count($decoded);

        if ($request->hasFile('cover_file')) {
            $project->cover_upload_id = $this->UploadImageCreate($request->file('cover_file'), 'uploads/reel-projects');
            $project->save();
        }

        try {
            DB::transaction(function () use ($decoded, $projectId, $sceneLength, $count, $project) {
                $keptSceneIds = [];

                foreach (array_values($decoded) as $index => $sceneData) {
                    if (!is_array($sceneData)) {
                        continue;
                    }

                    $sceneId = (int) ($sceneData['id'] ?? 0);
                    $scene   = $sceneId ? ReelScene::where('reel_project_id', $projectId)->find($sceneId) : null;
                    if (!$scene) {
                        $scene = new ReelScene(['reel_project_id' => $projectId]);
                    }

                    $scene->sort_order       = $index + 1;
                    $scene->duration_seconds = $sceneLength;
                    $scene->title            = (string) ($sceneData['title'] ?? '');
                    $scene->description      = (string) ($sceneData['description'] ?? '');
                    $scene->dialogue         = (string) ($sceneData['dialogue'] ?? '');
                    $scene->character_ids    = $this->cleanSceneCharacterIds($sceneData['characters'] ?? [], $project);
                    $scene->is_first_scene   = $index === 0;
                    $scene->is_last_scene    = $index === $count - 1;
                    $scene->save();

                    $keptSceneIds[] = $scene->id;
                }

                ReelScene::where('reel_project_id', $projectId)
                    ->whereNotIn('id', $keptSceneIds ?: [0])
                    ->delete(); // cascades to any leftover reel_scene_characters rows
            });

            $this->notifyReelAdminOnScenesSaved($projectId);

            return $this->responseWithSuccess('Scenes saved.', ['document' => $this->scriptDocument($projectId)]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Caps a scene's character list at 3 and drops anything not in the
     *  project's own involved-characters roster. */
    private function cleanSceneCharacterIds(array $ids, ReelProject $project): array
    {
        $allowed = $project->involvedCharacters()->pluck('id')->all();
        $ids     = array_values(array_unique(array_map('intval', $ids)));
        $ids     = array_values(array_filter($ids, fn ($id) => in_array($id, $allowed, true)));
        return array_slice($ids, 0, 3);
    }

    /** The whole-reel script/prompt document — project setup + every saved
     *  scene's Description/Dialogue, ready to download or hand to an AI
     *  video tool. Null if the project has no scenes yet. */
    public function scriptDocument(int $projectId): ?string
    {
        $project = ReelProject::with('scenes')->find($projectId);
        if (!$project || $project->scenes->isEmpty()) {
            return null;
        }
        return (new ReelDocumentBuilder())->build($project);
    }

    /** One saved scene's own standalone script. Null if the scene doesn't
     *  belong to this project. */
    public function sceneScriptDocument(int $projectId, int $sceneId): ?string
    {
        $project = ReelProject::find($projectId);
        $scene   = $project ? ReelScene::where('reel_project_id', $projectId)->find($sceneId) : null;
        if (!$project || !$scene) {
            return null;
        }
        return (new ReelDocumentBuilder())->buildScene($project, $scene);
    }

    /** If this project came from a Reels/Content Pool suggestion and
     *  whoever's saving isn't the admin who approved it, let that admin
     *  know the scene setup is ready for their review. */
    private function notifyReelAdminOnScenesSaved(int $projectId): void
    {
        $reel = PortalReel::where('reel_project_id', $projectId)->first();
        if (!$reel || !$reel->reviewed_by || (int) $reel->reviewed_by === (int) Auth::id()) {
            return;
        }
        PortalNotifier::send($reel->reviewed_by, 'Reel setup ready for review', $reel->title, route('portal-social-reels.show', $reel->id));
    }

    /**
     * AI-assisted scene generation. Builds a prompt from the project's own
     * title/hook/description/format/scene length/character roster, asks
     * the AI gateway for a full set of scenes (one Description + one
     * Dialogue each), validates and sanitizes the result, then REPLACES
     * the project's current scenes with the fresh AI set — it's filling in
     * the template's fixed number of boxes, not adding more of them. The
     * caller (a confirm() in the browser) is responsible for warning the
     * teacher this will overwrite anything already typed. Nothing is
     * written at all if the AI call fails.
     */
    public function aiGenerateScenes(int $projectId): array
    {
        $project = ReelProject::find($projectId);
        if (!$project) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $sceneCount = $this->sceneCount($project);
        if (!$sceneCount) {
            return $this->responseWithError('Set a target total length and a scene length in Reel Setup first.', []);
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

        $clean = $this->sanitizeAiScenes($scenes, $sceneCount, $characters);

        if (empty($clean)) {
            return $this->responseWithError('The AI response could not be read into valid scenes. Please try again.', []);
        }

        try {
            DB::transaction(function () use ($clean, $projectId, $project) {
                ReelScene::where('reel_project_id', $projectId)->delete(); // cascades to any reel_scene_characters rows

                $last = count($clean) - 1;
                foreach ($clean as $i => $sceneData) {
                    ReelScene::create([
                        'reel_project_id'  => $projectId,
                        'sort_order'       => $i + 1,
                        'duration_seconds' => $project->scene_length_seconds ?: 8,
                        'title'            => $sceneData['title'],
                        'description'      => $sceneData['description'],
                        'dialogue'         => $sceneData['dialogue'],
                        'character_ids'    => $sceneData['character_ids'],
                        'is_first_scene'   => $i === 0,
                        'is_last_scene'    => $i === $last,
                    ]);
                }
            });

            $this->aiHelperRepo->logUsage(
                Auth::id(),
                optional(Auth::user())->name,
                'teacher',
                'reel_scene_generation',
                "Reel #{$projectId} \"{$project->title}\" — {$sceneCount} scenes generated",
                $result['provider'] ?? null
            );

            return $this->responseWithSuccess(count($clean) . ' scene(s) generated — review and edit them below before saving.', []);
        } catch (\Throwable $th) {
            Log::warning('Reel Studio AI scene save failed: ' . $th->getMessage());
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** How many scenes this project's template calls for, or null until
     *  both a target length and a scene length are set. */
    public function sceneCount(ReelProject $project): ?int
    {
        if (!$project->target_duration_seconds || !$project->scene_length_seconds) {
            return null;
        }
        return max(1, (int) ceil($project->target_duration_seconds / $project->scene_length_seconds));
    }

    /**
     * Builds the AI instruction prompt for a full scene-generation run.
     *
     * Structure matters here, not just content: this is built FACTS FIRST,
     * then the admin's stylistic instructions, then a short closing
     * reminder + the JSON contract. Testing showed that appending the real
     * title/hook/description only at the very END of a long (several
     * thousand word) custom template meant the AI effectively never
     * reached it, or treated an illustrative example buried in the style
     * instructions as the real topic — a well-known LLM "lost in the
     * middle" failure mode. Leading with the real facts, then repeating
     * them briefly at the end too, makes them the most salient thing in
     * the prompt regardless of how long the admin's style template is.
     *
     * Also: correctness must NEVER depend on the admin's template using
     * the exact right placeholder tokens — the facts are included as
     * plain text here regardless of whether the custom template also
     * references {{title}} etc. itself (it's fine if it does too, that
     * just repeats them a third time, which only helps).
     */
    private function buildAiScenePrompt(ReelProject $project, $characters, int $sceneCount): string
    {
        $roster = $characters->map(function (ReelCharacter $c) {
            return "- \"{$c->name}\": appearance: " . ($c->appearance_text ?: '(not set)')
                . ' | voice: ' . ($c->voice_profile_text ?: '(not set)')
                . ' | rules: ' . ($c->rules_text ?: '(not set)');
        })->implode("\n");

        $globalInstructions = trim((string) setting('reel_global_instructions'));
        $targetSeconds       = $project->target_duration_seconds ?: 40;
        $sceneLength         = $project->scene_length_seconds ?: 8;
        $formatLabel         = ReelProject::FORMATS[$project->format] ?? ReelProject::FORMATS['vertical'];

        $sceneCountInstruction = "Create exactly {$sceneCount} scenes, each exactly {$sceneLength} seconds long, so the reel totals approximately {$targetSeconds} seconds.";

        $characterRotationNote = $characters->count() > 1
            ? "This reel has {$characters->count()} characters available: {$characters->pluck('name')->implode(', ')}. Each scene may feature AT MOST 3 of them (often just 1 or 2) — pick whichever characters actually belong in that specific scene, never the whole roster at once. If there are enough scenes, make sure EVERY character still gets a meaningful role somewhere across the whole reel — do not silently ignore any supplied character for the entire video."
            : '';

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
            '[NUMBER]'                    => (string) $sceneCount,
            '[NUMBER OF SCENES]'          => (string) $sceneCount,
            '[DURATION(S)]'               => (string) $sceneLength,
        ];

        // FACTS FIRST — the real, specific information for this exact video.
        $facts = <<<PROMPT
THE ACTUAL VIDEO TO CREATE RIGHT NOW. Everything below this point (any style guide, structure, rules, or illustrative examples) must be applied TO THIS EXACT TOPIC. If anything further down shows a sample topic, sample dialogue, or sample character for illustration, that is ONLY an example of writing style — it must NOT influence the actual subject matter, which is fixed here and must not change:

TITLE: {$project->title}
WHAT THIS VIDEO IS ACTUALLY ABOUT (the real subject — do not substitute a different topic for this): {$project->description}
OPENING HOOK (scene 1 opens with this exact line, spoken by Kea — the system adds it automatically, do not write it yourself): {$project->hook}
VIDEO FORMAT: {$formatLabel}
{$sceneCountInstruction}
{$characterRotationNote}
CHARACTERS AVAILABLE FOR THIS VIDEO (use ONLY these, by name, written directly into the dialogue as "Name: line" — never invent or substitute a different character). For EACH scene, pick 1-3 of them who actually appear in it and list those exact names in that scene's own "characters" array — dialogue in a scene must only ever come from the characters listed for that same scene:
{$roster}

THESE THREE RULES APPLY NO MATTER WHAT STYLE GUIDE FOLLOWS BELOW — they are structural requirements, not style choices:
1. TITLE: every scene needs a short 3-6 word title naming what that scene is about.
2. DESCRIPTION LENGTH: every scene's description must be 3-5 full sentences — specific setting details, exactly where each character is and what they're physically doing, camera framing/movement, lighting/mood, and the transition to the next scene. A one-line or one-sentence description is NOT acceptable, it is not enough for a video generator to actually render the shot.
3. EVERY PICKED CHARACTER MUST SPEAK: if a scene's "characters" array has 2 or 3 names in it, the dialogue MUST contain at least one line from EACH of those names — never pick 3 characters for a scene and then only write a line for 1 of them. The dialogue should read as a real back-and-forth exchange (2-4 lines total for a {$sceneLength}-second scene), not a single line.

---

PROMPT;

        $base = $customTemplate ? strtr($customTemplate, $placeholders) : <<<PROMPT
You are planning a short educational reel for a school, scene by scene, for use with Google Flow (an AI video generator). Every scene is exactly {$sceneLength} seconds.

{$globalInstructions}

For each scene write:
1. A short title (3-6 words) naming what that scene is about, e.g. "The Honest Mistake" or "Kea's Question".
2. A detailed, visually rich scene description — 3 to 5 full sentences, enough for a video generator to actually render the shot: the setting/background in specific detail, where each character is positioned and what they are physically doing, camera framing and movement, lighting and mood, and how the scene transitions into the next one. Do not write a one-line summary — describe it like a shot list a director could follow.
3. Pick 1-3 characters from the roster who actually appear in that scene.
4. A dialogue block that is a real back-and-forth exchange: if 2 or 3 characters are picked for the scene, EVERY one of them must have at least one line — never write dialogue for only one of the picked characters while leaving the others silent. Write each line as "Name: line" on its own line, 2-4 exchanges feels right for a {$sceneLength}-second scene. Leave the dialogue block empty only if the scene has zero characters in it. Make it feel like a real, engaging conversation, not a textbook explanation.
PROMPT;

        $closing = <<<PROMPT


---
Reminder: this video's real topic is "{$project->title}" ({$project->description}) — not any example topic shown above. The system automatically adds the opening hook line and the fixed closing line ("Brainbot: Think Smart." / "Kea: Learn Smarter.") to the first and last scene — do not write those yourself, just make those scenes lead naturally into them.

Respond with ONLY valid JSON (no markdown, no commentary, no code fences) matching exactly this schema:
{"scenes":[{"title":"...","description":"...","dialogue":"...","characters":["Name1","Name2"]}]}
PROMPT;

        return $facts . $base . $closing;
    }

    /** Validates/repairs the AI's scene list: keeps description/dialogue as
     *  plain strings, resolves each scene's "characters" names back to ids
     *  against the project's own roster (dropping anything that doesn't
     *  match, capping at 3), and caps the count at what the template calls
     *  for (padding with blank scenes if the AI returned fewer than that,
     *  as long as it returned at least one usable scene). */
    private function sanitizeAiScenes($scenes, int $sceneCount, $characters): array
    {
        $idByName = [];
        foreach ($characters as $c) {
            $idByName[strtolower(trim($c->name))] = $c->id;
        }

        $clean = [];

        foreach ($scenes as $sceneData) {
            if (!is_array($sceneData)) {
                continue;
            }

            $names = array_values(array_filter((array) ($sceneData['characters'] ?? [])));
            $ids   = [];
            foreach ($names as $name) {
                $id = $idByName[strtolower(trim((string) $name))] ?? null;
                if ($id !== null && !in_array($id, $ids, true)) {
                    $ids[] = $id;
                }
            }

            $clean[] = [
                'title'          => (string) ($sceneData['title'] ?? ''),
                'description'    => (string) ($sceneData['description'] ?? ''),
                'dialogue'       => (string) ($sceneData['dialogue'] ?? ''),
                'character_ids'  => array_slice($ids, 0, 3),
            ];

            if (count($clean) >= $sceneCount) {
                break;
            }
        }

        if (empty($clean)) {
            return [];
        }

        while (count($clean) < $sceneCount) {
            $clean[] = ['title' => '', 'description' => '', 'dialogue' => '', 'character_ids' => []];
        }

        return $clean;
    }
}
