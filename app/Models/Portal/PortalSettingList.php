<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/** One freely add/rename/delete-able "block" on the Social Board Settings
 *  page (Platforms, Formats, Pillars, ... or any custom one an admin adds).
 *  `key` is the stable identifier other code looks a block up by; `label`
 *  is the admin-editable heading shown on the page. */
class PortalSettingList extends Model
{
    protected $table = 'portal_setting_lists';
    protected $guarded = ['id'];

    protected $casts = [
        'values' => 'array',
    ];

    /** The live values for a known block key (e.g. 'reel_formats'), or []
     *  if that block was deleted or never seeded - callers already fall
     *  back to a hardcoded default when this comes back empty. */
    public static function valuesFor(string $key): array
    {
        $row = static::where('key', $key)->first();
        return $row ? (array) $row->values : [];
    }
}
