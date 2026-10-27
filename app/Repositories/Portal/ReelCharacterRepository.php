<?php

namespace App\Repositories\Portal;

use App\Models\Portal\ReelCharacter;
use App\Models\Portal\ReelSceneCharacter;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;

/** Admin-only catalogue of reusable reel characters (name, portrait,
 *  appearance/voice/rules text), plus the one shared global-instructions
 *  text block (stored as a plain Setting, like ai_helper_system_prompt). */
class ReelCharacterRepository
{
    use ReturnFormatTrait, CommonHelperTrait;

    private const UPLOAD_PATH = 'uploads/reel-characters';

    public function all()
    {
        return ReelCharacter::orderBy('sort_order')->orderBy('name')->get();
    }

    public function show(int $id): ?ReelCharacter
    {
        return ReelCharacter::find($id);
    }

    public function store($request): array
    {
        try {
            $portraitUploadId = $request->hasFile('portrait')
                ? $this->UploadImageCreate($request->file('portrait'), self::UPLOAD_PATH)
                : null;

            $character = ReelCharacter::create([
                'name'               => $request->name,
                'portrait_upload_id' => $portraitUploadId,
                'appearance_text'    => $request->appearance_text,
                'voice_profile_text' => $request->voice_profile_text,
                'rules_text'         => $request->rules_text,
                'sort_order'         => (int) ($request->sort_order ?? 0),
                'status'             => (int) ($request->status ?? 1),
            ]);

            return $this->responseWithSuccess('Character added.', ['id' => $character->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, int $id): array
    {
        $character = ReelCharacter::find($id);
        if (!$character) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        if ($request->hasFile('portrait')) {
            $character->portrait_upload_id = $this->UploadImageUpdate($request->file('portrait'), self::UPLOAD_PATH, $character->portrait_upload_id);
        }

        $character->name               = $request->name;
        $character->appearance_text    = $request->appearance_text;
        $character->voice_profile_text = $request->voice_profile_text;
        $character->rules_text         = $request->rules_text;
        $character->sort_order         = (int) ($request->sort_order ?? 0);
        $character->status             = (int) ($request->status ?? 1);
        $character->save();

        return $this->responseWithSuccess('Character updated.', ['id' => $character->id]);
    }

    /** Refuses to delete a character still used in any scene — asks the
     *  admin to deactivate instead, so a teacher's already-built scene
     *  never silently loses one of its characters' action points/dialogue. */
    public function destroy(int $id): array
    {
        $character = ReelCharacter::find($id);
        if (!$character) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        if (ReelSceneCharacter::where('reel_character_id', $id)->exists()) {
            return $this->responseWithError('This character is used in at least one scene — set it to Inactive instead of deleting it, so existing scenes keep working.', []);
        }

        $this->UploadImageDelete($character->portrait_upload_id);
        $character->delete();

        return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
    }

}
