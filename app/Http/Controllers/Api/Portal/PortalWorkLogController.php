<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Repositories\Portal\WorkLogRepository;
use App\Traits\ReturnFormatTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Thin JSON wrapper around the same WorkLogRepository the website's Work
 * Log screen uses (app/Http/Controllers/Portal/WorkLogController.php) --
 * same locking/overlap rules apply, since it's the same repository.
 */
class PortalWorkLogController extends Controller
{
    use ReturnFormatTrait;

    private $repo;

    public function __construct(WorkLogRepository $repo)
    {
        $this->repo = $repo;
    }

    private function actingStaffId(): ?int
    {
        $staff = Auth::user()->staff;
        return $staff ? (int) $staff->id : null;
    }

    public function today(Request $request)
    {
        try {
            $staffId = $this->actingStaffId();
            if ($staffId === null) {
                return $this->responseWithError('No staff record is linked to your account.');
            }

            $date = $request->query('date', now()->format('Y-m-d'));
            $entries = $this->repo->entriesForDay($staffId, $date);

            return $this->responseWithSuccess(___('alert.success'), [
                'date'    => $date,
                'locked'  => $this->repo->isLocked($staffId, $date),
                'entries' => $entries,
            ]);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong'), $th->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $staffId = $this->actingStaffId();
            if ($staffId === null) {
                return $this->responseWithError('No staff record is linked to your account.');
            }

            $request->validate([
                'date'       => 'required|date',
                'start_time' => 'required',
                'end_time'   => 'required',
            ]);

            $result = $this->repo->saveEntry($request, $staffId);

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
