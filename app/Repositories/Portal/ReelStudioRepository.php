<?php

namespace App\Repositories\Portal;

use App\Models\Portal\ReelProject;
use App\Models\Portal\ReelScene;
use App\Models\Portal\ReelSceneCharacter;
use App\Services\Reel\ReelPromptBuilder;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

/** Teacher-facing reel projects/scenes. Entirely separate from the ops
 *  Social Board's own `portal_reels` pipeline — no link between them. */
class ReelStudioRepository
{
    use ReturnFormatTrait;

    private const MAX_CHARACTERS_PER_SCENE = 3;

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

    public function storeProject($request, int $userId): array
    {
        try {
            $project = ReelProject::create([
                'title'      => $request->title,
                'topic'      => $request->topic,
                'created_by' => $userId,
            ]);

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

        $project->title = $request->title;
        $project->topic = $request->topic;
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
                    $scene->duration_seconds  = max(1, (int) ($sceneData['duration_seconds'] ?? 8));
                    $scene->description       = (string) ($sceneData['description'] ?? '');
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
