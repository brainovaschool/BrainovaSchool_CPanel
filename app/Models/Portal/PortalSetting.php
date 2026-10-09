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
        'activities'      => 'array',
    ];

    public const DEFAULT_REVISION_SCORES = [5, 4, 3, 2, 1, 0];
    public const DEFAULT_CATEGORIES      = ['General', 'Content', 'Social Media', 'Design', 'Admin'];
    public const DEFAULT_ACTIVITIES      = ['Meeting', 'Email & Admin', 'Training', 'Research', 'Break'];

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
