<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/** An employee's own remembered custom time for one of the day's template
 *  slots - see the migration for why it's keyed by the slot's original,
 *  un-customized start time rather than its position. */
class PortalWorkLogSlotPreference extends Model
{
    protected $table = 'portal_work_log_slot_preferences';
    protected $guarded = ['id'];
}
