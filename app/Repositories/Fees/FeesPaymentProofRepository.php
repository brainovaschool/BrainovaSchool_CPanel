<?php

namespace App\Repositories\Fees;

use App\Enums\Settings;
use App\Models\Accounts\AccountHead;
use App\Models\Accounts\Income;
use App\Models\Fees\FeesCollect;
use App\Models\Fees\FeesPaymentProof;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

/** The "I already paid another way" flow — a parent/student who doesn't
 *  want to put a card online uploads proof (a screenshot, usually) of a
 *  JazzCash/EasyPaisa/bank transfer/cash payment instead. This never
 *  touches fees_collects directly; see the migration's docblock for why.
 *  Approving one here is what actually turns it into a real, counted
 *  payment (reuses the same FeesCollect + Income bookkeeping the cash and
 *  online-gateway paths already use, so every existing fee/report/income
 *  screen picks it up automatically once approved). */
class FeesPaymentProofRepository
{
    use ReturnFormatTrait, CommonHelperTrait;

    public function __construct(private FeesCollectRepository $feesCollectRepository)
    {
    }

    public function forStudent(int $studentId)
    {
        return FeesPaymentProof::with(['feesAssignChildren.feesMaster.type'])
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->get();
    }

    public function pendingQueue()
    {
        return FeesPaymentProof::with(['student', 'feesAssignChildren.feesMaster.type'])
            ->where('status', FeesPaymentProof::PENDING)
            ->orderBy('id')
            ->paginate(Settings::PAGINATE);
    }

    public function reviewed(string $status)
    {
        return FeesPaymentProof::with(['student', 'feesAssignChildren.feesMaster.type', 'reviewer'])
            ->where('status', $status)
            ->orderByDesc('reviewed_at')
            ->paginate(Settings::PAGINATE);
    }

    public function show(int $id): ?FeesPaymentProof
    {
        return FeesPaymentProof::with(['student', 'feesAssignChildren.feesMaster.type', 'proofUpload', 'reviewer'])->find($id);
    }

    /** $ownedStudentIds: same ownership rule as online payment — the fee
     *  being claimed-as-paid must actually be one of this student's (or,
     *  from the Parent panel, one of the parent's children's) own fees. */
    public function store($request, array $ownedStudentIds, int $submittedByUserId): array
    {
        $feesAssignChildren = $this->feesCollectRepository->findOwnedFee((int) $request->fees_assign_children_id, $ownedStudentIds);
        if (!$feesAssignChildren) {
            return $this->responseWithError("This fee doesn't belong to your account.", []);
        }
        if ($feesAssignChildren->feesCollect) {
            return $this->responseWithError('This fee has already been paid.', []);
        }
        if (FeesPaymentProof::where('fees_assign_children_id', $feesAssignChildren->id)->where('status', FeesPaymentProof::PENDING)->exists()) {
            return $this->responseWithError('You already have a submission for this fee waiting on review.', []);
        }

        if (!$request->hasFile('proof_file')) {
            return $this->responseWithError('Please attach a screenshot or photo of your payment.', []);
        }

        try {
            $uploadId = $this->UploadImageCreate($request->file('proof_file'), 'uploads/fees-payment-proofs');
            if (!$uploadId) {
                return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
            }

            FeesPaymentProof::create([
                'fees_assign_children_id' => $feesAssignChildren->id,
                'student_id'              => $feesAssignChildren->student_id,
                'submitted_by_user_id'    => $submittedByUserId,
                'payment_method'          => $request->payment_method,
                'amount_claimed'          => $request->amount_claimed,
                'transaction_reference'   => $request->transaction_reference,
                'paid_date'               => $request->paid_date,
                'proof_upload_id'         => $uploadId,
                'note'                    => $request->note,
                'status'                  => FeesPaymentProof::PENDING,
            ]);

            return $this->responseWithSuccess('Submitted — a staff member will check it and confirm your payment.', []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Approving creates the real FeesCollect record — $confirmedAmount is
     *  whatever the staff member actually confirms (not necessarily the
     *  parent's claimed amount), since this is the one place a human signs
     *  off on what was really received. $reviewerUserId is a users.id
     *  (matching FeesCollect's own fees_collect_by FK — see the model
     *  docblock for why this can't be a staff id). */
    public function approve(int $id, int $reviewerUserId, float $confirmedAmount, ?string $note = null): array
    {
        $proof = FeesPaymentProof::with('feesAssignChildren')->find($id);
        if (!$proof) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($proof->status !== FeesPaymentProof::PENDING) {
            return $this->responseWithError('This submission has already been reviewed.', []);
        }
        if ($proof->feesAssignChildren->feesCollect) {
            return $this->responseWithError('This fee has already been paid through another route.', []);
        }

        DB::transaction(function () use ($proof, $reviewerUserId, $confirmedAmount, $note) {
            $feesCollect = FeesCollect::create([
                'date'                    => $proof->paid_date,
                'payment_method'          => 1, // Cash bucket — the specific method is kept in payment_gateway below
                'payment_gateway'         => FeesPaymentProof::METHODS[$proof->payment_method] ?? ucfirst($proof->payment_method),
                'transaction_id'          => $proof->transaction_reference,
                'fees_assign_children_id' => $proof->fees_assign_children_id,
                'amount'                  => $confirmedAmount,
                'fine_amount'             => 0,
                'fees_collect_by'         => $reviewerUserId,
                'student_id'              => $proof->student_id,
                'session_id'              => setting('session'),
            ]);

            $acHead = AccountHead::where('type', 1)->where('status', 1)->first();
            if ($acHead) {
                Income::create([
                    'fees_collect_id' => $feesCollect->id,
                    'name'            => env('APP_NAME') . '_' . $proof->fees_assign_children_id,
                    'session_id'      => setting('session'),
                    'income_head'     => $acHead->id,
                    'date'            => $proof->paid_date,
                    'amount'          => $confirmedAmount,
                ]);
            }

            $proof->status            = FeesPaymentProof::APPROVED;
            $proof->reviewed_by       = $reviewerUserId;
            $proof->reviewed_at       = now();
            $proof->fees_collect_id   = $feesCollect->id;
            if ($note) {
                $proof->note = trim(($proof->note ? $proof->note . "\n\n" : '') . 'Staff note: ' . $note);
            }
            $proof->save();
        });

        return $this->responseWithSuccess('Approved — the fee is now recorded as paid.', []);
    }

    public function reject(int $id, int $reviewerUserId, string $reason): array
    {
        $proof = FeesPaymentProof::find($id);
        if (!$proof) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($proof->status !== FeesPaymentProof::PENDING) {
            return $this->responseWithError('This submission has already been reviewed.', []);
        }

        $proof->status            = FeesPaymentProof::REJECTED;
        $proof->reviewed_by       = $reviewerUserId;
        $proof->reviewed_at       = now();
        $proof->rejection_reason  = $reason;
        $proof->save();

        return $this->responseWithSuccess('Rejected — the submitter will see your reason and can resubmit.', []);
    }
}
