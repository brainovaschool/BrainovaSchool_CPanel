<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Repositories\Portal\SocialRepository;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;

/**
 * Thin JSON wrapper around the same SocialRepository the website's Social
 * Board -> Reels screen uses (app/Http/Controllers/Portal/SocialController.php).
 */
class PortalReelController extends Controller
{
    use ReturnFormatTrait;

    private $repo;

    public function __construct(SocialRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        try {
            $reels = $this->repo->reels($request->only(['status', 'category', 'format']));
            return $this->responseWithSuccess(___('alert.success'), $reels);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }
}
