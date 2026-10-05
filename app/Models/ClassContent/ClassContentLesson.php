<?php

namespace App\Models\ClassContent;

use App\Models\BaseModel;
use App\Traits\ResolvesVideoEmbed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassContentLesson extends BaseModel
{
    use ResolvesVideoEmbed;

    protected $guarded = ['id'];

    public function module(): BelongsTo
    {
        return $this->belongsTo(ClassContentModule::class, 'module_id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ClassContentMaterial::class, 'lesson_id')->orderBy('sort_order');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ClassContentActivity::class, 'lesson_id')->orderBy('sort_order');
    }

    public function outcomes(): HasMany
    {
        return $this->hasMany(ClassContentOutcome::class, 'lesson_id')->orderBy('sort_order');
    }
}
