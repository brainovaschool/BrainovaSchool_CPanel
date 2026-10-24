<?php

namespace App\Models;

class DecisionRule extends BaseModel
{
    protected $guarded = ['id'];

    public function subjectRole()
    {
        return $this->belongsTo(Role::class, 'subject_role_id');
    }

    public function viewerRole()
    {
        return $this->belongsTo(Role::class, 'viewer_role_id');
    }
}
