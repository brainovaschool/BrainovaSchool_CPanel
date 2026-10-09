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

    /** Legacy single-owner column — left in the schema but no longer
     *  read anywhere; see owners() below. */
    public function owner()
    {
        return $this->belongsTo(Staff::class, 'owner_staff_id');
    }

    /** The real, current source of truth — a responsibility can have
     *  any number of owners at once ("1 person, two or more" — admin's
     *  own words). */
    public function owners()
    {
        return $this->belongsToMany(Staff::class, 'portal_responsibility_owners', 'responsibility_id', 'staff_id')->withTimestamps();
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

    /** For phase 8 (Social board) to check "who currently holds this
     *  permission-carrying duty" — any number of staff ids, empty if
     *  unowned or the duty doesn't exist. */
    public static function ownerStaffIdsForKey(string $key): array
    {
        $duty = static::where('key', $key)->first();
        return $duty ? $duty->owners()->pluck('staff.id')->all() : [];
    }

    /** Same, as users.id — for notifications. */
    public static function ownerUserIdsForKey(string $key): array
    {
        $duty = static::where('key', $key)->with('owners')->first();
        return $duty ? $duty->owners->pluck('user_id')->filter()->values()->all() : [];
    }
}
