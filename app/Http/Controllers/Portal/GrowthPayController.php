<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Portal\GrowthPayRepository;

/** Growth Pay — admin-only (portal_manage), per the approved design:
 *  she enters and effectively approves the numbers in one step, the
 *  portal only ever records money after she's actually paid it. */
class GrowthPayController extends Controller
{
    private $repo;

    public function __construct(GrowthPayRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $month = $request->filled('month') ? $request->month : now()->format('Y-m');

        $data['month'] = $month;
        $data['rows']  = $this->repo->rowsForMonth($month);
        $data['title'] = 'Team Portal — Growth Pay';
        return view('portal.growth-pay.index', compact('data'));
    }

    public function save(Request $request)
    {
        $request->validate([
            'staff_id'     => 'required|exists:staff,id',
            'month'        => 'required|date_format:Y-m',
            'base_amount'  => 'nullable|numeric|min:0',
            'bonus_amount' => 'nullable|numeric|min:0',
        ]);

        $result = $this->repo->save($request, (int) $request->staff_id, $request->month);
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function markPaid($id)
    {
        $result = $this->repo->markPaid((int) $id, Auth::id());
        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
