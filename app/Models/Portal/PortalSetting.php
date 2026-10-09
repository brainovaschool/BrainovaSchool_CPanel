<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $table = 'portal_settings';
    protected $guarded = ['id'];

    protected $casts = [
        'revision_scores' => 'array',
        'categories'      => 'array',
    ];

    public const DEFAULT_REVISION_SCORES = [5, 4, 3, 2, 1, 0];
    public const DEFAULT_CATEGORIES      = ['General', 'Content', 'Social Media', 'Design', 'Admin'];

    /** Always exactly one row — created with sensible defaults the first
     *  time anything asks for it. */
    public static function current(): self
    {
        $row = static::first();
        if (!$row) {
            $row = static::create([
                'revision_scores' => self::DEFAULT_REVISION_SCORES,
                'categories'      => self::DEFAULT_CATEGORIES,
            ]);
        }
        return $row;
    }

    /** index = revision count, capped at the table's last index. */
    public function scoreForRevisionCount(int $count): int
    {
        $scores = $this->revision_scores ?: self::DEFAULT_REVISION_SCORES;
        $index  = min($count, count($scores) - 1);
        return (int) ($scores[$index] ?? 0);
    }
}
