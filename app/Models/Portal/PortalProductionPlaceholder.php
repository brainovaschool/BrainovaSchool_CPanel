<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/** A Production Plan day reserved for a Series before a specific Topic/
 *  reel has been picked yet - see the migration for the full story. */
class PortalProductionPlaceholder extends Model
{
    protected $table = 'portal_production_placeholders';
    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
    ];
}
