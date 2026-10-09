<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalResponsibility extends Model
{
    protected $table = 'portal_responsibilities';
    protected $guarded = ['id'];

    public const KEY_CONTENT  = 'content';
    public const KEY_AUDIENCE = 'audience';

    public function owner()
    {
        return $this->belongsTo(Staff::class, 'owner_staff_id');
    }

    public function ticks()
    {
        return $this->hasMany(PortalDutyTick::class, 'responsibility_id');
    }

    /** Today's date for a Daily duty, this week's Monday for a Weekly one —
     *  matches the spec's DutyTick entity ("date for Daily, Monday date
     *  for Weekly"). */
    public function currentPeriod(): string
    {
        return $this->freq === 'weekly'
            ? now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d')
            : now()->format('Y-m-d');
    }

    public function isDoneForCurrentPeriod(): bool
    {
        return $this->ticks()->where('period', $this->currentPeriod())->exists();
    }

    /** For a later phase (8, Social board) to check "who currently holds
     *  this permission-carrying duty" without caring whether it's an
     *  employee or still sitting with the admin. Returns a users.id. */
    public static function ownerUserIdForKey(string $key): ?int
    {
        $duty = static::where('key', $key)->with('owner')->first();
        return $duty && $duty->owner ? $duty->owner->user_id : null;
    }
}
