<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;

class AvatarItem extends BaseModel
{
    protected $guarded = ['id'];

    /** The four layers a student's look is built from, bottom to top.
     *  'avatar' is the base character — every student always has exactly
     *  one; the rest are optional overlays. 'accessory' is the only one a
     *  student can wear several of at once. */
    public const CATEGORIES = [
        'avatar'    => 'Base Character',
        'outfit'    => 'Outfit',
        'hat'       => 'Hat',
        'accessory' => 'Accessory',
    ];

    /** Starting placement for a brand-new item of each category — a rough
     *  "about where this usually sits" so the admin only fine-tunes rather
     *  than positioning from scratch. A base character always fills the
     *  whole stage. */
    public const DEFAULT_PLACEMENT = [
        'avatar'    => ['pos_x' => 50, 'pos_y' => 50, 'scale' => 100, 'rotation' => 0],
        'outfit'    => ['pos_x' => 50, 'pos_y' => 58, 'scale' => 60,  'rotation' => 0],
        'hat'       => ['pos_x' => 50, 'pos_y' => 22, 'scale' => 40,  'rotation' => 0],
        'accessory' => ['pos_x' => 50, 'pos_y' => 45, 'scale' => 30,  'rotation' => 0],
    ];

    protected $casts = [
        'pos_x' => 'float',
        'pos_y' => 'float',
        'scale' => 'float',
    ];

    /** Which Dashboard Features row governs each optional layer, and whether
     *  it shows when no row exists yet. Outfits and hats stay hidden until
     *  someone deliberately switches them on; accessories are on out of the
     *  box. (The general dashboard_feature_enabled() helper is fail-open, so
     *  it can't express an off-by-default section.) */
    public const SECTION_GATES = [
        'outfit'    => ['key' => 'avatar_outfits',     'default' => false],
        'hat'       => ['key' => 'avatar_hats',        'default' => false],
        'accessory' => ['key' => 'avatar_accessories', 'default' => true],
    ];

    public static function sectionEnabled(string $category): bool
    {
        // Base Character has no gate — an avatar has to be something.
        $gate = self::SECTION_GATES[$category] ?? null;
        if (!$gate) {
            return true;
        }

        static $flags = null;
        if ($flags === null) {
            try {
                $flags = \App\Models\WebsiteSetup\DashboardFeature::where('portal', 'student')
                    ->pluck('status', 'feature_key')
                    ->map(fn ($status) => (int) $status)
                    ->all();
            } catch (\Throwable $e) {
                $flags = [];
            }
        }

        return array_key_exists($gate['key'], $flags) ? $flags[$gate['key']] === 1 : $gate['default'];
    }

    /** Categories the school currently has switched on (Website Setup >
     *  Dashboard Features). */
    public static function enabledCategories(): array
    {
        return array_filter(
            self::CATEGORIES,
            fn ($key) => self::sectionEnabled($key),
            ARRAY_FILTER_USE_KEY
        );
    }

    public function scopeActive($query)
    {
        return $query->where('status', \App\Enums\Status::ACTIVE);
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
