<?php

namespace App\Models\Portal;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalTaskSubmission extends Model
{
    protected $table = 'portal_task_submissions';
    protected $guarded = ['id'];

    public const REVISION = 'revision';
    public const APPROVED = 'approved';

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(PortalTask::class, 'task_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class, 'upload_id');
    }
}
