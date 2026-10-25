<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalReel extends Model
{
    protected $table = 'portal_reels';
    protected $guarded = ['id'];

    public const SUGGESTED  = 'suggested';
    public const ACCEPTED   = 'accepted';
    public const PRODUCTION = 'production';
    public const READY      = 'ready';
    public const PUBLISHED  = 'published';
    public const REJECTED   = 'rejected';

    /** Business rule 10: "counts toward a month when accepted (or later)
     *  and has a planned date in that month" — i.e. anything from
     *  accepted onward, excluding rejected and still-suggested. */
    public const COUNTS_TOWARD_MONTH = [self::ACCEPTED, self::PRODUCTION, self::READY, self::PUBLISHED];

    protected $casts = [
        'planned_date'       => 'date',
        'deadline'           => 'date',
        'reviewed_at'        => 'datetime',
        'prompt_approved_at' => 'datetime',
        'video_approved_at'  => 'datetime',
        'is_open_paid'       => 'boolean',
        'custom_fields'      => 'array',
    ];

    public function suggestedBy()
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function assignee()
    {
        return $this->belongsTo(Staff::class, 'assigned_to');
    }

    public function promptUpload()
    {
        return $this->belongsTo(Upload::class, 'prompt_upload_id');
    }

    public function thumbUpload()
    {
        return $this->belongsTo(Upload::class, 'thumb_upload_id');
    }

    /** One human-readable label summarising where this reel actually
     *  stands right now - the table shows this instead of the raw status
     *  enum, since "assigned to X" / "prompt pending approval" etc. carry
     *  the real progress, not just the broad suggested/accepted/... stage. */
    public function statusLabel(): string
    {
        if ($this->status === self::REJECTED) {
            return 'Rejected';
        }
        if ($this->status === self::PUBLISHED) {
            return 'Uploaded';
        }
        if ($this->video_approved_at) {
            return $this->planned_date ? 'Approved — uploading ' . $this->planned_date->format('d M Y') : 'Approved — pick an upload date';
        }
        if ($this->drive_link) {
            return 'Video pending approval';
        }
        if ($this->prompt_approved_at) {
            return 'Prompt approved — awaiting video';
        }
        if ($this->prompt_link) {
            return 'Prompt pending approval';
        }
        if ($this->is_open_paid) {
            return 'Open (Paid Task)';
        }
        if ($this->assigned_to) {
            return 'Assigned to ' . trim(optional($this->assignee)->first_name . ' ' . optional($this->assignee)->last_name);
        }
        return 'Suggested';
    }
}
