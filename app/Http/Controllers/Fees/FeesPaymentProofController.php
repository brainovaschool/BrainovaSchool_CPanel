<?php

namespace App\Http\Controllers\Fees;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Fees\FeesPaymentProof;
use App\Repositories\Fees\FeesPaymentProofRepository;

/** Staff-side review queue for the "I paid another way, here's my proof"
 *  submissions — this is the only place a submission actually becomes a
 *  counted payment (see FeesPaymentProofRepository::approve()). */
class FeesPaymentProofController extends Controller
{
    private $repo;

    public function __construct(FeesPaymentProofRepository $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $status = $request->get('status', FeesPaymentProof::PENDING);
        $data['proofs']  = $status === FeesPaymentProof::PENDING ? $this->repo->pendingQueue() : $this->repo->reviewed($status);
        $data['status']  = $status;
        $data['title']   = 'Payment Proof Review';
        return view('backend.fees.payment-proof.index', compact('data'));
    }

    public function show($id)
    {
        $data['item'] = $this->repo->show($id);
        if (!$data['item']) {
            return redirect()->route('fees-payment-proof.index')->with('danger', ___('alert.not_found'));
        }
        $data['title'] = 'Review Payment Proof';
        return view('backend.fees.payment-proof.show', compact('data'));
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'confirmed_amount' => 'required|numeric|min:0',
            'note'             => 'nullable|string|max:1000',
        ]);

        $result = $this->repo->approve((int) $id, (int) Auth::id(), (float) $request->confirmed_amount, $request->note);

        return redirect()->route('fees-payment-proof.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $result = $this->repo->reject((int) $id, (int) Auth::id(), $request->rejection_reason);

        return redirect()->route('fees-payment-proof.index')
            ->with($result['status'] ? 'success' : 'danger', $result['message']);
    }
}
