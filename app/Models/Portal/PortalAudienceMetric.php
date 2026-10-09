<?php

namespace App\Models\Portal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalAudienceMetric extends Model
{
    protected $table = 'portal_audience_metrics';
    protected $guarded = ['id'];

    protected $casts = [
        'date'   => 'date',
        'values' => 'array',
    ];

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
