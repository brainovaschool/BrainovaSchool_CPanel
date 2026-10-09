<?php

namespace App\Repositories\Fees;

use App\Models\AssignFeesDiscount;
use App\Models\EarlyPaymentDiscount;
use App\Models\Setting;
use Stripe\Charge;
use Stripe\Stripe;
use App\Models\Accounts\Income;
use App\Models\Fees\FeesCollect;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Fees\FeesAssignChildren;
use App\Interfaces\Fees\FeesMasterInterface;
use App\Interfaces\Fees\FeesCollectInterface;
use App\Models\Accounts\AccountHead;
use App\Models\StudentInfo\SessionClassStudent;
use App\Models\StudentInfo\Student;

class FeesCollectRepository implements FeesCollectInterface
{
    use ReturnFormatTrait;

    private $model;
    private $feesMasterRepo;

    public function __construct(FeesCollect $model, FeesMasterInterface $feesMasterRepo)
    {
        $this->model          = $model;
        $this->feesMasterRepo = $feesMasterRepo;
    }

    public function all()
    {
        return $this->model->active()->get();
    }

    public function getPaginateAll()
    {
        return $this->model::latest()->paginate(10);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            foreach ($request->fees_assign_childrens as $key=>$item) {
                $row                   = new $this->model;
                $row->date             = $request->date;
                $row->payment_method   = $request->payment_method;
                $row->fees_assign_children_id   = $item;
                $row->amount           = $request->amounts[$key] + $request->fine_amounts[$key] ?? 0;
                $row->fine_amount      = $request->fine_amounts[$key];
                $row->fees_collect_by  = Auth::user()->id;
                $row->student_id       = $request->student_id;
                $row->session_id       = setting('session');
                $row->save();

               $ac_head =  AccountHead::where('type', 1)->where('status', 1)->first();


               if($ac_head){
                    $incomeStore                   = new Income();
                    $incomeStore->fees_collect_id  = $row->id;
                    $incomeStore->name             = $item;
                    $incomeStore->session_id       = setting('session');
                    $incomeStore->income_head      = $ac_head->id; // Because, Fees id 1.
                    $incomeStore->date             = $request->date;
                    $incomeStore->amount           = $row->amount;
                    $incomeStore->invoice_number   = 'fees_collect_'.$item;
                    $incomeStore->save();
               }

                $tax = calculateTax($row->amount);
                $settings = Setting::whereIn('name', ['tax_income_head'])->pluck('value', 'name');
                $accountHead = AccountHead::where('name', $settings['tax_income_head'])->first();
                if ($tax > 0 && $settings) {
                    $incomeStore = new Income();
                    $incomeStore->name = "Fees-Tax";
                    $incomeStore->session_id = setting('session');
                    $incomeStore->income_head = $accountHead->id;
                    $incomeStore->date = $request->date;
                    $incomeStore->amount = $tax;
                    $incomeStore->save();
                }

                if ($request->early_payment_percentage > 0){
                    $feesDiscount = new AssignFeesDiscount();
                    $feesDiscount->fees_assign_children_id = $item;
                    $feesDiscount->title = 'Early Payment Fees Discount';
                    $feesDiscount->discount_amount = calculateDiscount($row->amount, $request->early_payment_percentage);
                    $feesDiscount->discount_percentage = $request->early_payment_percentage;
                    $feesDiscount->discount_source = 'Early Payment Fees Discount';
                    $feesDiscount->save();
                }



            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function feesAssigned($id) // student id
    {

        $groups = FeesAssignChildren::withCount('feesCollect')->with(['feesCollect', 'feesDiscount'])->where('student_id', $id);
        $groups = $groups->whereHas('feesAssign', function ($query) {
            return $query->where('session_id', setting('session'));
        });

        return $groups->get();
    }

    public function update($request, $id)
    {
        try {
            $row                = $this->model->findOrfail($id);
            $row->name          = $request->name;
            $row->code          = $request->code;
            $row->description   = $request->description;
            $row->status        = $request->status;
            $row->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $row = $this->model->find($id);
            $row->delete();

            $income = Income::where('invoice_number', 'fees_collect_'.$row->fees_assign_children_id)->first();
            if($income){
                $income->delete();
            }
            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function getFeesAssignStudents($request)
    {
        $students = SessionClassStudent::query();
        $students = $students->where('session_id', setting('session'));
        if($request->class != "") {

            $students = $students->where('classes_id', $request->class);
        }

        if($request->section != "") {

            $students = $students->where('section_id', $request->section);
        }

        if($request->name != "") {
            $students = $students->whereHas('student', function ($query) use ($request) {
                return $query->where('first_name', $request->name)->orWhere('last_name', $request->name);
            });
        }

        if($request->student != "") {
            $students = $students->where('student_id', $request->student);
        }

        return $students->paginate(10);
    }

    public function feesShow($request)
    {
        $data['fees_assign_children'] = $this->feesAssigned($request->student_id)->whereIn('id', $request->fees_assign_childrens);

        $data['student_id']           = $request->student_id;
        $data['discount_amount']      = $request->discount_amount;
        return $data;
    }

    /** Looks a fee assignment up ONLY if it actually belongs to one of
     *  $ownedStudentIds — every online-payment entry point (Stripe, PayPal,
     *  for both the Student and Parent panels) must call this before
     *  touching a fees_assign_children_id that came from the client,
     *  otherwise a signed-in user could pay (or probe) a fee that isn't
     *  theirs just by changing the id in the request/session. */
    public function findOwnedFee(int $feesAssignChildrenId, array $ownedStudentIds): ?FeesAssignChildren
    {
        $row = FeesAssignChildren::with(['feesMaster.type', 'feesDiscount', 'feesCollect'])
            ->find($feesAssignChildrenId);

        if (!$row || !in_array((int) $row->student_id, $ownedStudentIds, true)) {
            return null;
        }

        return $row;
    }

    /** The one authoritative total for a fee assignment, computed entirely
     *  from server-side data (fee master amount, tax, the assignment's own
     *  discount, the student's special discount, and whether it's overdue
     *  and still unpaid) — never from anything the client sends. Shared by
     *  Stripe and PayPal so both gateways charge the exact same amount for
     *  the same fee; previously Stripe took its charge amount straight from
     *  the POSTed form (meaning the browser could submit any number) and
     *  separately didn't apply the student's special discount the way
     *  PayPal already did. */
    private function calculateFeeTotal(FeesAssignChildren $feesAssignChildren): array
    {
        $total = (float) ($feesAssignChildren->feesMaster?->amount ?? 0);
        $total += calculateTax($total);

        $student = Student::with('specialDiscount.discount')->find($feesAssignChildren->student_id);
        $specialDiscount = $student?->specialDiscount?->discount;
        $specialDiscountValue = $specialDiscount
            ? ($specialDiscount->type == 'F' ? (float) $specialDiscount->discount : round(($specialDiscount->discount / 100) * $total, 2))
            : 0;

        if ($feesAssignChildren->feesDiscount) {
            $total -= calculateDiscount($total, $feesAssignChildren->feesDiscount->discount_percentage);
        }

        $fineAmount = 0;
        $alreadyPaid = $feesAssignChildren->relationLoaded('feesCollect')
            ? $feesAssignChildren->feesCollect
            : $feesAssignChildren->feesCollect()->first();
        if (!$alreadyPaid && $feesAssignChildren->feesMaster?->due_date && date('Y-m-d') > $feesAssignChildren->feesMaster->due_date) {
            $fineAmount = (float) $feesAssignChildren->feesMaster?->fine_amount;
            $total += $fineAmount;
        }

        $total -= $specialDiscountValue;

        return ['total' => round($total, 2), 'fine_amount' => $fineAmount];
    }

    /** $ownedStudentIds: the fee being paid must belong to one of these —
     *  the logged-in student's own id, or (from the Parent panel) every
     *  child linked to that parent's account. */
    public function payWithStripeStore($request, array $ownedStudentIds): array
    {
        return DB::transaction(function () use ($request, $ownedStudentIds) {
            $feesAssignChildren = $this->findOwnedFee((int) $request->fees_assign_children_id, $ownedStudentIds);
            if (!$feesAssignChildren) {
                return $this->responseWithError("This fee doesn't belong to your account.", []);
            }
            if ($feesAssignChildren->feesCollect) {
                return $this->responseWithError('This fee has already been paid.', []);
            }

            ['total' => $total, 'fine_amount' => $fineAmount] = $this->calculateFeeTotal($feesAssignChildren);

            Stripe::setApiKey(Setting('stripe_secret'));
            $description = 'Pay ' . $total . ' for ' . $feesAssignChildren->feesMaster?->type?->name . ' of ' . env('APP_NAME');

            $charge = Charge::create([
                "amount" => (int) round($total * 100),
                "currency" => "usd",
                "source" => $request->stripeToken,
                "description" => $description
            ]);

            $this->feeCollectStoreByStripe($feesAssignChildren, $total, $fineAmount, $request->date, @$charge->balance_transaction);

            return $this->responseWithSuccess(___('alert.Fee has been paid successfully'), []);
        });
    }

    protected function feeCollectStoreByStripe(FeesAssignChildren $feesAssignChildren, float $amount, float $fineAmount, $date, $transaction_id)
    {
        $feesCollect = FeesCollect::create([
            'date'                      => $date,
            'payment_method'            => 2,
            'payment_gateway'           => 'Stripe',
            'transaction_id'            => $transaction_id,
            'fees_assign_children_id'   => $feesAssignChildren->id,
            'amount'                    => $amount,
            'fine_amount'               => $fineAmount,
            'fees_collect_by'           => 1, // Because student/parent can not be collect so that's why we use first admin user id.
            'student_id'                => $feesAssignChildren->student_id,
            'session_id'                => setting('session')
        ]);

            $ac_head =  AccountHead::where('type', 1)->where('status', 1)->first();

            if($ac_head){
                $incomeStore                   = new Income();
                $incomeStore->fees_collect_id  = $feesCollect->id;
                $incomeStore->name             = env('APP_NAME').'_'.$feesAssignChildren->id;
                $incomeStore->session_id       = setting('session');
                $incomeStore->income_head      = $ac_head->id; // Because, Fees id 1.
                $incomeStore->date             = $date;
                $incomeStore->amount           = $feesCollect->amount;
                $incomeStore->save();
            }
    }




    public function paypalOrderData($invoice_no, $success_route, $cancel_route)
    {
        $feesAssignChildren = FeesAssignChildren::with(['feesMaster.type', 'feesDiscount', 'feesCollect'])
            ->find(session()->get('FeesAssignChildrenID'));

        ['total' => $total] = $this->calculateFeeTotal($feesAssignChildren);

        $description = 'Pay ' . $total . ' for ' . $feesAssignChildren->feesMaster?->type?->name;

        $data                           = [];
        $data['items']                  = [];
        $data['invoice_id']             = $invoice_no;
        $data['invoice_description']    = $description;
        $data['return_url']             = $success_route;
        $data['cancel_url']             = $cancel_route;
        $data['total']                  = $total;

        return $data;
    }




    public function feeCollectStoreByPaypal($response, $feesAssignChildren)
    {
        DB::transaction(function () use ($response, $feesAssignChildren) {

            ['total' => $amount, 'fine_amount' => $fine_amount] = $this->calculateFeeTotal($feesAssignChildren);

            $date = date('Y-m-d', strtotime($response['PAYMENTINFO_0_ORDERTIME']));

            $feesCollect = FeesCollect::create([
                'date'                      => $date,
                'payment_method'            => 2,
                'payment_gateway'           => 'PayPal',
                'transaction_id'            => $response['PAYMENTINFO_0_TRANSACTIONID'],
                'fees_assign_children_id'   => $feesAssignChildren->id,
                'amount'                    => $amount,
                'fine_amount'               => $fine_amount,
                'fees_collect_by'           => 1, // Because student/parent can not be collect so that's why we use first admin user id.
                'student_id'                => $feesAssignChildren->student_id,
                'session_id'                => setting('session')
            ]);

            Income::create([
                'fees_collect_id'           => $feesCollect->id,
                'name'                      => $feesAssignChildren->id,
                'session_id'                => setting('session'),
                'income_head'               => 1, // Because, Fees id 1.
                'date'                      => $date,
                'amount'                    => $amount
            ]);
        });
    }
}
