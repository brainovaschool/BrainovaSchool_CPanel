<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

/** A reel/topic series - its `defaults` (Pillar, Format, and every Reel
 *  form custom field) pre-fill the Reel form the moment this series is
 *  picked, so a content creator suggesting a topic only has to type the
 *  topic and hook; everything else comes from here. */
class PortalReelSeries extends Model
{
    protected $table = 'portal_reel_series';
    protected $guarded = ['id'];
    protected $casts = ['defaults' => 'array'];
}
