<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvatarItem extends BaseModel
{
    protected $guarded = ['id'];

    /** The four layers a student's look is built from, bottom to top,
     *  plus two more categories that live in this same table but are
     *  never worn and never reach the student's Shop tab — Hub (a themed
     *  zone on My Learning Island, e.g. "CodeNova") and Building (a
     *  structure that belongs to one Hub). They reuse this table purely
     *  because they need the exact same tools: an image upload and the
     *  pos_x/pos_y/scale/rotation placement editor. price_coins is simply
     *  unused for both. See sectionEnabled() — 'avatar' is the base
     *  character — every student always has exactly one; the rest are
     *  optional overlays. 'accessory' is the only one a student can wear
     *  several of at once. */
    public const CATEGORIES = [
        'avatar'    => 'Outfit',
        'outfit'    => 'Clothing Layer',
        'hat'       => 'Hat',
        'accessory' => 'Accessory',
        'hub'       => 'Hub',
        'building'  => 'Building',
    ];

    /** Categories placed on My Learning Island rather than worn by the
     *  avatar. Admin-only content: never gated by Dashboard Features,
     *  never shown on the student's Shop tab. */
    public const ISLAND_CATEGORIES = ['hub', 'building'];

    /** Starting placement for a brand-new item of each category — a rough
     *  "about where this usually sits" so the admin only fine-tunes rather
     *  than positioning from scratch. A base character always fills the
     *  whole stage. Hub/Building placements are percentages across the
     *  island banner, not the avatar stage — much smaller by default since
     *  that canvas is a wide scene, not a square close-up. */
    public const DEFAULT_PLACEMENT = [
        'avatar'    => ['pos_x' => 50, 'pos_y' => 50, 'scale' => 100, 'rotation' => 0],
        'outfit'    => ['pos_x' => 50, 'pos_y' => 58, 'scale' => 60,  'rotation' => 0],
        'hat'       => ['pos_x' => 50, 'pos_y' => 22, 'scale' => 40,  'rotation' => 0],
        'accessory' => ['pos_x' => 50, 'pos_y' => 45, 'scale' => 30,  'rotation' => 0],
        'hub'       => ['pos_x' => 50, 'pos_y' => 50, 'scale' => 16,  'rotation' => 0],
        'building'  => ['pos_x' => 50, 'pos_y' => 60, 'scale' => 9,   'rotation' => 0],
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

    /** A Building's Hub. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /** A Hub's Buildings. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('sort_order');
    }
}
