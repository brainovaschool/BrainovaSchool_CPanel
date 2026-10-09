<?php

namespace App\Models\Fees;

use App\Models\BaseModel;
use App\Models\Upload;
use App\Models\User;
use App\Models\StudentInfo\Student;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeesPaymentProof extends BaseModel
{
    protected $guarded = ['id'];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'paid_date'   => 'date',
    ];

    public const PENDING  = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    public const STATUSES = [
        self::PENDING  => 'Pending review',
        self::APPROVED => 'Approved',
        self::REJECTED => 'Rejected',
    ];

    public const METHODS = [
        'jazzcash'      => 'JazzCash',
        'easypaisa'     => 'EasyPaisa',
        'bank_transfer' => 'Bank Transfer',
        'cash'          => 'Cash',
        'other'         => 'Other',
    ];

    public function feesAssignChildren(): BelongsTo
    {
        return $this->belongsTo(FeesAssignChildren::class, 'fees_assign_children_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function proofUpload(): BelongsTo
    {
        return $this->belongsTo(Upload::class, 'proof_upload_id');
    }

    /** reviewed_by stores a users.id, matching FeesCollect's own
     *  fees_collect_by convention (a hard FK to users, not staff — the
     *  seeded Super Admin has no Staff row at all, so staff ids can't be
     *  relied on to always exist for whoever is reviewing). */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function feesCollect(): BelongsTo
    {
        return $this->belongsTo(FeesCollect::class, 'fees_collect_id');
    }
}
