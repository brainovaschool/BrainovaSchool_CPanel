<?php

namespace App\Models\WebsiteSetup;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssessmentLead extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'link_sent_at' => 'datetime',
        'whatsapp_sent_at' => 'datetime',
        'meeting_reminder_sent_at' => 'datetime',
        'meeting_confirmed_at' => 'datetime',
    ];

    const STATUS_NEW = 'new';
    const STATUS_INVITED = 'invited';
    const STATUS_TESTED = 'tested';
    const STATUS_REPORTED = 'reported';
    const STATUS_BOOKED_MEETING = 'booked_meeting';
    const STATUS_ENROLLED = 'enrolled';

    const STATUS_LABELS = [
        self::STATUS_NEW => 'New',
        self::STATUS_INVITED => 'Invited',
        self::STATUS_TESTED => 'Tested',
        self::STATUS_REPORTED => 'Report shared',
        self::STATUS_BOOKED_MEETING => 'Meeting booked',
        self::STATUS_ENROLLED => 'Enrolled',
    ];

    public function slot()
    {
        return $this->belongsTo(AssessmentSlot::class, 'assessment_slot_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }
}
