<?php

namespace App\Models\Portal;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ReelProject extends Model
{
    protected $table = 'reel_projects';
    protected $guarded = ['id'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scenes()
    {
        return $this->hasMany(ReelScene::class)->orderBy('sort_order');
    }
}
