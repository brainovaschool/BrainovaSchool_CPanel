<?php

namespace App\Models\Academic;

use App\Models\Academic\ClassSetup;
use App\Models\BaseModel;
use App\Models\ClassTranslate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Classes extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name','status','sort_order'
    ];

    protected $appends = ['class_tran'];

    public function getClassTranAttribute()
    {
        $translation = $this->defaultTranslate()->first();
        return $translation->name ?? $this->name;

    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }
    public function classSetup()
    {
        return $this->hasOne(ClassSetup::class);
    }

    public function defaultTranslate()
    {
        $relation = $this->hasOne(ClassTranslate::class, 'class_id')->where('locale', request()->locale ?? config('app.locale'));
        if ($relation->exists()) {
            return $relation;
        } else {
            return $this->hasOne(ClassTranslate::class, 'class_id')->where('locale', 'en');
        }
    }


    public function translations()
    {
        return $this->hasMany(ClassTranslate::class, 'class_id', 'id');
    }
}
