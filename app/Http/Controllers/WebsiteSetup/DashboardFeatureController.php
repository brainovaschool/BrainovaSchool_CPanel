<?php

namespace App\Http\Controllers\WebsiteSetup;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\WebsiteSetup\DashboardFeatureRepository;

class DashboardFeatureController extends Controller
{
    private $repo;

    public function __construct(DashboardFeatureRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $portal = in_array($request->get('portal'), ['student', 'teacher', 'parent'], true)
            ? $request->get('portal')
            : 'student';

        $data['portal']   = $portal;
        $data['features'] = $this->repo->getByPortal($portal);
        $data['title']    = ___('settings.dashboard_features');

        return view('website-setup.dashboard-feature.index', compact('data'));
    }

    public function bulkDelete(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkDestroy($ids);
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.deleted'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }

    public function bulkStatus(Request $request)
    {
        $ids = array_filter((array) $request->input('ids', []));
        if (empty($ids)) {
            return response()->json([___('alert.select_at_least_one_row'), 'warning', ___('alert.attention'), ___('alert.OK')]);
        }
        $result = $this->repo->bulkStatus($ids, (int) $request->input('status', 1));
        if ($result['status']) {
            return response()->json([$result['message'], 'success', ___('alert.updated'), ___('alert.OK')]);
        }
        return response()->json([$result['message'], 'error', ___('alert.oops'), ___('alert.OK')]);
    }
}
