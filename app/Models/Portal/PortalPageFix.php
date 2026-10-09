<?php

namespace App\Models\Portal;

use App\Models\Staff\Staff;
use Illuminate\Database\Eloquent\Model;

class PortalPageFix extends Model
{
    protected $table = 'portal_page_fixes';
    protected $guarded = ['id'];

    public function owner()
    {
        return $this->belongsTo(Staff::class, 'owner_staff_id');
    }
}
