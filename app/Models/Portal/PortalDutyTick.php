<?php

namespace App\Models\Portal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalDutyTick extends Model
{
    protected $table = 'portal_duty_ticks';
    protected $guarded = ['id'];

    protected $casts = [
        'period'    => 'date',
        'ticked_at' => 'datetime',
    ];

    public function responsibility()
    {
        return $this->belongsTo(PortalResponsibility::class, 'responsibility_id');
    }

    public function tickedBy()
    {
        return $this->belongsTo(User::class, 'ticked_by');
    }
}
