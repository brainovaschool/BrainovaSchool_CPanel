<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Portal\PortalReel;
use App\Repositories\Portal\ReelStudioRepository;
use App\Repositories\Portal\SocialRepository;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Thin JSON wrapper around the same SocialRepository the website's Social
 * Board -> Reels screen uses (app/Http/Controllers/Portal/SocialController.php).
 */
class PortalReelController extends Controller
{
    use ReturnFormatTrait;

    private $repo;
    private $studioRepo;

    public function __construct(SocialRepository $repo, ReelStudioRepository $studioRepo)
    {
        $this->repo = $repo;
        $this->studioRepo = $studioRepo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    public function index(Request $request)
    {
        try {
            $reels = $this->repo->reels($request->only(['status', 'category', 'format', 'series_name']));
            return $this->responseWithSuccess(___('alert.success'), $reels);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    /** For the "Suggest a Reel" series dropdown. */
    public function series()
    {
        try {
            return $this->responseWithSuccess(___('alert.success'), $this->repo->reelSeries());
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    /** The simplified "Suggest a Reel" flow (series + topic + hook) --
     *  same requireContentAccess() rule the website's Suggest Content form
     *  enforces, checked here directly since this isn't behind portal_manage. */
    public function store(Request $request)
    {
        try {
            if (!$this->repo->canManageContent($this->actingStaffId())) {
                return $this->responseWithError("Only admin or whoever holds the Content responsibility can suggest a reel.");
            }

            $request->validate([
                'title'  => 'required|string|max:150',
                'format' => 'nullable|string|max:20',
            ]);

            $result = $this->repo->storeReel($request, Auth::id());
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

    /** The bridge into Reel Studio from an assigned/approved reel -- same
     *  rule as the website's "Open" button (portal-social-reels.setup):
     *  content managers, or whoever the reel is actually assigned to. */
    public function openStudio($id)
    {
        try {
            $reel = PortalReel::find((int) $id);
            if (!$reel) {
                return $this->responseWithError(___('alert.not_found'));
            }

            $staffId = $this->actingStaffId();
            $isAssignee = $staffId !== null && (int) $reel->assigned_to === $staffId;
            if (!$this->repo->canManageContent($staffId) && !$isAssignee) {
                return $this->responseWithError("You're not assigned to this reel.");
            }
            if ($reel->status === PortalReel::SUGGESTED) {
                return $this->responseWithError('Approve this suggestion before setting it up.');
            }

            $project = $this->studioRepo->openOrCreateReelProject($reel, Auth::id());
            return $this->responseWithSuccess(___('alert.success'), ['project_id' => $project->id]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}
