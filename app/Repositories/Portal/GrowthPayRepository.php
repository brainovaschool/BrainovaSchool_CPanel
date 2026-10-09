<?php

namespace App\Repositories\Portal;

use App\Models\Accounts\AccountHead;
use App\Models\Accounts\Expense;
use App\Models\Portal\PortalGrowthPay;
use App\Models\Staff\Staff;
use App\Services\Portal\PortalNotifier;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

/** Growth Pay — handoff spec phase 8, built to the design the admin
 *  confirmed: she enters/approves the base+bonus numbers herself (no
 *  automated milestone engine), transfers the money herself outside the
 *  system, and "Mark Paid" here just records that as a real Expense —
 *  same pattern as phase 7's paid-task payouts, same promise: never a
 *  student fee entry, never payable twice. */
class GrowthPayRepository
{
    use ReturnFormatTrait;

    public function forMonth(string $month)
    {
        return PortalGrowthPay::with('staff')->where('month', $month)->get()->keyBy('staff_id');
    }

    /** Every active staff member for the month, each paired with their
     *  Growth Pay row if one already exists. */
    public function rowsForMonth(string $month)
    {
        $byStaff = $this->forMonth($month);
        return Staff::active()->orderBy('first_name')->get()->map(fn (Staff $s) => [
            'staff' => $s,
            'pay'   => $byStaff->get($s->id),
        ]);
    }

    public function save($request, int $staffId, string $month): array
    {
        $existing = PortalGrowthPay::where('staff_id', $staffId)->where('month', $month)->first();
        if ($existing && $existing->status === 'paid') {
            return $this->responseWithError('This month is already paid and can\'t be changed.', []);
        }

        PortalGrowthPay::updateOrCreate(
            ['staff_id' => $staffId, 'month' => $month],
            [
                'base_amount'  => $request->base_amount ?: 0,
                'bonus_amount' => $request->bonus_amount ?: 0,
                'note'         => $request->note,
            ]
        );

        return $this->responseWithSuccess('Saved.', []);
    }

    public function markPaid(int $id, int $actingUserId): array
    {
        $pay = PortalGrowthPay::with('staff')->find($id);
        if (!$pay) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($pay->status === 'paid') {
            return $this->responseWithError('This has already been marked paid.', []);
        }
        if ($pay->total <= 0) {
            return $this->responseWithError('Enter a base or bonus amount before marking it paid.', []);
        }
        if (!$pay->staff) {
            return $this->responseWithError('No staff record to pay.', []);
        }

        $head = AccountHead::where('name', 'Staff Growth Pay')->where('type', 2)->first();
        if (!$head) {
            return $this->responseWithError('The "Staff Growth Pay" expense category hasn\'t been set up yet — visit the one-off seed link first.', []);
        }

        DB::transaction(function () use ($pay, $actingUserId, $head) {
            $employeeName = trim($pay->staff->first_name . ' ' . $pay->staff->last_name);

            $expense = new Expense();
            $expense->session_id   = setting('session');
            $expense->name         = "Team Portal growth pay — {$employeeName} ({$pay->month})";
            $expense->expense_head = $head->id;
            $expense->date         = now()->format('Y-m-d');
            $expense->amount       = $pay->total;
            $expense->description  = "Growth pay #{$pay->id} — {$employeeName}, {$pay->month}: base {$pay->base_amount} + bonus {$pay->bonus_amount}";
            $expense->save();

            $pay->status           = 'paid';
            $pay->marked_paid_by   = $actingUserId;
            $pay->marked_paid_at   = now();
            $pay->expense_id       = $expense->id;
            $pay->save();
        });

        if ($pay->staff->user_id) {
            PortalNotifier::send($pay->staff->user_id, 'Payment marked as paid', "Growth pay for {$pay->month}");
        }

        return $this->responseWithSuccess('Marked paid — recorded as an expense.', []);
    }
}
