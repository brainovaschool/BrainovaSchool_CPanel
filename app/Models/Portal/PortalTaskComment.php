<?php

namespace App\Models\Portal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PortalTaskComment extends Model
{
    protected $table = 'portal_task_comments';
    protected $guarded = ['id'];

    public function task()
    {
        return $this->belongsTo(PortalTask::class, 'task_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
