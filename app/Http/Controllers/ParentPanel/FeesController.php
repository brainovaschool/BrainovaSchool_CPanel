<?php

namespace App\Http\Controllers\ParentPanel;

use App\Models\EarlyPaymentDiscount;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Fees\FeesAssignChildren;
use App\Models\StudentInfo\Student;
use App\Models\StudentInfo\ParentGuardian;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Srmklive\PayPal\Services\ExpressCheckout;
use App\Repositories\Fees\FeesCollectRepository;
use App\Repositories\Fees\FeesPaymentProofRepository;
use App\Repositories\ParentPanel\FeesRepository;

class FeesController extends Controller
{
    private $repo;
    private $feesCollectRepository;
    private $feesPaymentProofRepository;

    function __construct(FeesRepository $repo, FeesCollectRepository $feesCollectRepository, FeesPaymentProofRepository $feesPaymentProofRepository)
    {
        $this->repo = $repo;
        $this->feesCollectRepository = $feesCollectRepository;
        $this->feesPaymentProofRepository = $feesPaymentProofRepository;
    }

    /** Every child linked to the logged-in parent's account — a fee being
     *  paid must belong to one of these, so a parent can only ever pay
     *  (or even look up) their own children's fees. */
    private function ownedStudentIds(): array
    {
        $parent = ParentGuardian::where('user_id', Auth::id())->first();
        if (!$parent) {
            return [];
        }
        return Student::where('parent_guardian_id', $parent->id)->pluck('id')->toArray();
    }

    public function index(Request $request){
        $data = $this->repo->index($request);
        $student = Student::with('specialDiscount.discount', 'feesMasters.type')->find(request()->student_id);
        $disc['discount'] = @$student->specialDiscount?->discount;
        $data['payment_proofs'] = $request->student_id ? $this->feesPaymentProofRepository->forStudent((int) $request->student_id) : collect();
        return view('parent-panel.fees', compact('data', 'disc'));
    }


    public function payModal(Request $request)
    {
        $now = date('Y-m-d');
        $discount = EarlyPaymentDiscount::whereDate('start_date', '<=', $now)
            ->whereDate('end_date', '>=', $now)
            ->first();
        $feesAssignChild = FeesAssignChildren::with('feesMaster')->where('id', $request->fees_assigned_children_id)->first();
        $student = Student::with('specialDiscount.discount', 'feesMasters.type')->find($feesAssignChild->student_id);
        $specialDiscount = @$student->specialDiscount?->discount;
        return view('common.fee-pay.fee-pay-modal', [
            'discount' => $discount,
            'specialDiscount' => $specialDiscount,
            'feeAssignChildren' => $feesAssignChild,
            'formRoute' => route('parent-panel-fees.pay-with-stripe'),
            'paypalRoute' => route('parent-panel-fees.pay-with-paypal'),
        ]);
    }

    public function proofModal(Request $request)
    {
        return view('common.fee-pay.fee-proof-modal', [
            'feeAssignChildren' => FeesAssignChildren::with('feesMaster')->where('id', $request->fees_assigned_children_id)->first(),
            'formRoute' => route('parent-panel-fees.submit-payment-proof'),
        ]);
    }

    public function submitPaymentProof(Request $request)
    {
        $request->validate([
            'fees_assign_children_id' => 'required|integer',
            'payment_method'          => 'required|in:jazzcash,easypaisa,bank_transfer,cash,other',
            'amount_claimed'          => 'required|numeric|min:0',
            'paid_date'               => 'required|date',
            'transaction_reference'   => 'nullable|string|max:150',
            'note'                    => 'nullable|string|max:1000',
            'proof_file'              => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:5120',
        ]);

        $result = $this->feesPaymentProofRepository->store($request, $this->ownedStudentIds(), Auth::id());

        return back()->with($result['status'] ? 'success' : 'danger', $result['message']);
    }


    public function payWithStripe(Request $request)
    {
        try {
            $result = $this->feesCollectRepository->payWithStripeStore($request, $this->ownedStudentIds());

            return back()->with($result['status'] ? 'success' : 'danger', $result['message']);

        } catch (\Throwable $th) {
            return back()->with('danger', ___('alert.something_went_wrong_please_try_again'));
        }
    }


    public function payWithPaypal(Request $request)
    {
        if (!$this->feesCollectRepository->findOwnedFee((int) $request->fees_assign_children_id, $this->ownedStudentIds())) {
            return back()->with('danger', "This fee doesn't belong to your account.");
        }

        loadPayPalCredentials();

        Session::put('FeesAssignChildrenID', $request->fees_assign_children_id);

        $provider   = new ExpressCheckout;
        $data       = $this->feesCollectRepository->paypalOrderData(uniqid(), route('parent-panel-fees.payment.success'), route('parent-panel-fees.payment.cancel'));
        $response   = $provider->setExpressCheckout($data);

        return redirect($response['paypal_link']);
    }





    public function paymentSuccess(Request $request)
    {
        loadPayPalCredentials();

        try {
            $provider   = new ExpressCheckout;
            $token      = $request->token;
            $PayerID    = $request->PayerID;
            $response   = $provider->getExpressCheckoutDetails($token);

            $invoiceID  = $response['INVNUM'] ?? uniqid();
            $data       = $this->feesCollectRepository->paypalOrderData($invoiceID, route('parent-panel-fees.payment.success'), route('parent-panel-fees.payment.cancel'));
            $response   = $provider->doExpressCheckoutPayment($data, $token, $PayerID);

            $feesAssignChildren = optional(FeesAssignChildren::with('feesMaster')->where('id', session()->get('FeesAssignChildrenID'))->first());

            if ($feesAssignChildren && $response['PAYMENTINFO_0_TRANSACTIONID']) {
                $this->feesCollectRepository->feeCollectStoreByPaypal($response, $feesAssignChildren);
            }

            session()->forget('FeesAssignChildrenID');

            return redirect()->route('parent-panel-fees.index', ['student_id' => $feesAssignChildren->student_id])->with('success', ___('alert.Fee has been paid successfully'));

        } catch (\Throwable $th) {

            return redirect()->route('parent-panel-fees.index')->with('danger', ___('alert.something_went_wrong_please_try_again'));
        }
    }





    public function paymentCancel()
    {
        return redirect()->route('parent-panel-fees.index')->with('danger', ___('alert.Payment cancelled!'));
    }
}
