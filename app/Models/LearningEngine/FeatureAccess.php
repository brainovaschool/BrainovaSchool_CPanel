<?php

namespace App\Models\LearningEngine;

class FeatureAccess extends \App\Models\BaseModel
{
    protected $table = 'feature_access';

    protected $guarded = ['id'];

    protected $casts = [
        'visible_to_all' => 'boolean',
    ];

    /** Every student-facing module this screen can gate. Keep labels
     *  short — they're the heading on each tab of the admin screen. */
    public const FEATURES = [
        'avatar'     => 'Avatar',
        'island'     => 'Learning Island',
        'ai_helper'  => 'AI Helper',
    ];

    public static function isVisibleFor(string $featureKey, int $studentId): bool
    {
        if (static::visibleToAll($featureKey)) {
            return true;
        }

        return in_array($studentId, static::testerIds($featureKey), true);
    }

    public static function visibleToAll(string $featureKey): bool
    {
        return (bool) static::where('feature_key', $featureKey)->value('visible_to_all');
    }

    public static function testerIds(string $featureKey): array
    {
        return FeatureAccessStudent::where('feature_key', $featureKey)
            ->pluck('student_id')->map(fn ($id) => (int) $id)->all();
    }
}
