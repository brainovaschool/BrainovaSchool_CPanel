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

    /** The moment a reel first leaves "suggested" (however that happens -
     *  reviewReel(), or assignReel()'s own suggested-to-accepted bump) it
     *  gets a code: its series' own code plus the next running number in
     *  that series, e.g. "SS1". Left blank if the series has no code set,
     *  and never reassigned once it has one - a single model hook instead
     *  of repeating this at every place status can change. */
    protected static function booted()
    {
        static::saving(function (PortalReel $reel) {
            if ($reel->code || !$reel->series_name || $reel->status === self::SUGGESTED || $reel->status === self::REJECTED) {
                return;
            }
            $series = PortalReelSeries::where('name', $reel->series_name)->first();
            if (!$series || !$series->code) {
                return;
            }
            $next = static::where('series_name', $reel->series_name)->whereNotNull('code')->count() + 1;
            $reel->code = $series->code . $next;
        });
    }

    public function suggestedBy()
    {
        return $this->belongsTo(User::class, 'suggested_by');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** series_name is a plain string copy, not a foreign key, but
     *  PortalReelSeries.name is unique - this matches against it so a
     *  loaded reel can still reach its series' own colour/code without a
     *  separate lookup everywhere series_name is displayed. */
    public function seriesModel()
    {
        return $this->belongsTo(PortalReelSeries::class, 'series_name', 'name');
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

    /** The Reel Studio project (scenes, AI-generated prompts) this
     *  suggestion's setup work happens in - null until an admin opens it
     *  for the first time (see SocialRepository::openOrCreateReelProject()). */
    public function reelProject()
    {
        return $this->belongsTo(ReelProject::class, 'reel_project_id');
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
        if ($this->status !== self::SUGGESTED) {
            return 'Approved — awaiting assignment';
        }
        return 'Suggested';
    }

    /** Which badge-basic-*-text colour matches statusLabel() - same
     *  branching, just a colour instead of a sentence. */
    public function statusBadgeClass(): string
    {
        if ($this->status === self::REJECTED) {
            return 'badge-basic-danger-text';
        }
        if ($this->status === self::PUBLISHED) {
            return 'badge-basic-success-text';
        }
        if ($this->video_approved_at) {
            return 'badge-basic-success-text';
        }
        if ($this->drive_link || $this->prompt_link) {
            return 'badge-basic-warning-text';
        }
        if ($this->prompt_approved_at) {
            return 'badge-basic-info-text';
        }
        if ($this->is_open_paid || $this->assigned_to) {
            return 'badge-basic-primary-text';
        }
        if ($this->status !== self::SUGGESTED) {
            return 'badge-basic-info-text';
        }
        return 'badge-basic-secondary-text';
    }

    /** Background tint for the whole row on the Reels table - same
     *  cascade as statusLabel()/statusBadgeClass(), but its own colours
     *  since "uploaded" specifically needed to read as blue, not the
     *  green already used for the Status badge. Empty string (no tint)
     *  for a still-suggested reel, so a plain new row stays plain. */
    public function rowStateClass(): string
    {
        if ($this->status === self::REJECTED) {
            return 'bn-row-rejected';
        }
        if ($this->status === self::PUBLISHED) {
            return 'bn-row-uploaded';
        }
        if ($this->video_approved_at) {
            return 'bn-row-complete';
        }
        if ($this->drive_link || $this->prompt_link) {
            return 'bn-row-in-progress';
        }
        if ($this->deadline || $this->is_open_paid || $this->assigned_to) {
            return 'bn-row-assigned';
        }
        if ($this->status !== self::SUGGESTED) {
            return 'bn-row-approved';
        }
        return '';
    }

    /** Colour for the small "P" (prompt) progress dot on the Reels table. */
    public function promptBadgeClass(): string
    {
        if ($this->prompt_approved_at) {
            return 'badge-basic-success-text';
        }
        if ($this->prompt_link) {
            return 'badge-basic-warning-text';
        }
        return 'badge-basic-secondary-text';
    }

    /** Colour for the small "V" (video) progress dot on the Reels table. */
    public function videoBadgeClass(): string
    {
        if ($this->video_approved_at) {
            return 'badge-basic-success-text';
        }
        if ($this->drive_link) {
            return 'badge-basic-warning-text';
        }
        return 'badge-basic-secondary-text';
    }
}
