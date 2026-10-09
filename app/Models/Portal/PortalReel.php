<?php

namespace App\Models\Portal;

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
        'planned_date' => 'date',
        'reviewed_at'  => 'datetime',
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

    public function promptUpload()
    {
        return $this->belongsTo(Upload::class, 'prompt_upload_id');
    }

    public function thumbUpload()
    {
        return $this->belongsTo(Upload::class, 'thumb_upload_id');
    }
}
