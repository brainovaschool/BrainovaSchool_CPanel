<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Repositories\Portal\ReelStudioRepository;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Thin JSON wrapper around the same ReelStudioRepository the website's
 * Reel Studio uses (app/Http/Controllers/Portal/ReelStudioController.php).
 * Phase 1 for mobile: view a project and its scenes, and save the scene
 * list (description + dialogue per scene). Project setup (characters,
 * duration, custom fields), AI scene generation, and per-scene prompt
 * downloads stay website-only for now.
 */
class PortalReelStudioController extends Controller
{
    use ReturnFormatTrait;

    private $repo;

    public function __construct(ReelStudioRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    private function authorized($project): bool
    {
        return $this->repo->authorizeProject($project, (int) Auth::id(), $this->actingStaffId(), hasPermission('portal_manage'));
    }

    public function show($id)
    {
        try {
            $project = $this->repo->find((int) $id);
            if (!$this->authorized($project)) {
                return $this->responseWithError(___('alert.not_found'));
            }
            return $this->responseWithSuccess(___('alert.success'), $project);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    /** Body: scenes_json -- a JSON-encoded string, same shape the website's
     *  Scene Builder sends: [{id?, description, dialogue}, ...]. Replaces
     *  the whole scene list, same as web. Returns the compiled script
     *  document text instead of streaming a file download. */
    public function saveScenes(Request $request, $id)
    {
        try {
            $project = $this->repo->find((int) $id);
            if (!$this->authorized($project)) {
                return $this->responseWithError(___('alert.not_found'));
            }

            $request->validate(['scenes_json' => 'required|string']);

            $result = $this->repo->saveScenes($request, (int) $id);
            if ($result['status']) {
                return $this->responseWithSuccess($result['message'], $result['data']);
            }
            return $this->responseWithError($result['message']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->responseWithError(___('alert.validation_error'), $e->errors());
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}
