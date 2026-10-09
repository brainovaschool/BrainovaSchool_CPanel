<?php

namespace App\Models\Portal;

use App\Models\Accounts\Expense;
use App\Models\Staff\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalPayout extends Model
{
    protected $table = 'portal_payouts';
    protected $guarded = ['id'];

    protected $casts = [
        'marked_paid_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(PortalTask::class, 'task_id');
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    public function markedPaidBy()
    {
        return $this->belongsTo(User::class, 'marked_paid_by');
    }

    public function expense()
    {
        return $this->belongsTo(Expense::class, 'expense_id');
    }
}
