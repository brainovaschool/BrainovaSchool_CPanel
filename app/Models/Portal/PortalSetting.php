<?php

namespace App\Models\Portal;

use Illuminate\Database\Eloquent\Model;

class PortalSetting extends Model
{
    protected $table = 'portal_settings';
    protected $guarded = ['id'];

    protected $casts = [
        'revision_scores'           => 'array',
        'categories'                => 'array',
        'activities'                => 'array',
        'content_assignee_role_ids' => 'array',
    ];

    public const DEFAULT_REVISION_SCORES = [5, 4, 3, 2, 1, 0];
    public const DEFAULT_CATEGORIES      = ['General', 'Content', 'Social Media', 'Design', 'Admin'];
    public const DEFAULT_ACTIVITIES      = ['Meeting', 'Email & Admin', 'Training', 'Research', 'Break'];
    public const DEFAULT_PLATFORMS       = ['Instagram', 'Facebook', 'TikTok'];
    public const DEFAULT_SOCIAL_METRICS  = ['Views', 'Followers', 'Likes'];
    // "Pillar" in the UI - reel_categories is the underlying column/keyword,
    // kept as-is since renaming it would mean a data migration for no
    // functional gain; only the label changed.
    public const DEFAULT_REEL_CATEGORIES = ['Educational', 'Promo', 'Entertainment', 'Behind the scenes'];
    public const DEFAULT_REEL_FORMATS    = ['Reel', 'Carousel', 'Post'];
    // No sensible universal default - series are project-specific, the
    // admin defines their own from a blank list.
    public const DEFAULT_SERIES_NAMES    = [];

    /** These 5 used to be fixed JSON columns on this row. Social Board
     *  Settings is now a freely add/rename/delete-able set of blocks
     *  (portal_setting_lists) instead, so reading e.g. $settings->reel_formats
     *  transparently routes to the live block with that key in that table -
     *  every existing caller (_form.blade.php, index.blade.php,
     *  audience.blade.php, ...) keeps working completely unchanged. */
    private const LIST_KEYS = ['social_platforms', 'social_metrics', 'reel_categories', 'reel_formats', 'series_names'];

    public function getAttribute($key)
    {
        if (in_array($key, self::LIST_KEYS, true)) {
            return PortalSettingList::valuesFor($key);
        }
        return parent::getAttribute($key);
    }

    /** Always exactly one row — created with sensible defaults the first
     *  time anything asks for it. */
    public static function current(): self
    {
        $row = static::first();
        if (!$row) {
            $row = static::create([
                'revision_scores'    => self::DEFAULT_REVISION_SCORES,
                'categories'         => self::DEFAULT_CATEGORIES,
                'work_day_hours'     => 8,
                'day_start'          => '09:00',
                'lunch_after_hours'  => 4,
                'activities'         => self::DEFAULT_ACTIVITIES,
            ]);
        }
        return $row;
    }

    /** One-hour rows for a full day: workday hours' worth of work slots,
     *  with a single lunch slot inserted once lunch_after_hours have been
     *  worked (business rule 7 / spec's "pre-filled as one-hour rows with
     *  a lunch hour"). Purely a display/validation template — nothing is
     *  stored until the employee actually picks something for a slot. */
    public function daySlots(): array
    {
        $hours      = max(1, (int) round($this->work_day_hours ?: 8));
        $lunchAfter = max(0, (int) round($this->lunch_after_hours ?: 4));
        $cursor     = \Carbon\Carbon::createFromFormat('H:i', $this->day_start ?: '09:00');

        $slots = [];
        $worked = 0;
        for ($i = 0; $i < $hours; $i++) {
            if ($lunchAfter > 0 && $worked === $lunchAfter) {
                $slots[] = ['start' => $cursor->format('H:i'), 'end' => $cursor->copy()->addHour()->format('H:i'), 'is_lunch' => true];
                $cursor->addHour();
            }
            $slots[] = ['start' => $cursor->format('H:i'), 'end' => $cursor->copy()->addHour()->format('H:i'), 'is_lunch' => false];
            $cursor->addHour();
            $worked++;
        }

        return $slots;
    }

    /** index = revision count, capped at the table's last index. */
    public function scoreForRevisionCount(int $count): int
    {
        $scores = $this->revision_scores ?: self::DEFAULT_REVISION_SCORES;
        $index  = min($count, count($scores) - 1);
        return (int) ($scores[$index] ?? 0);
    }
}
