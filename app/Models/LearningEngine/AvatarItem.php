<?php

namespace App\Models\LearningEngine;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvatarItem extends BaseModel
{
    protected $guarded = ['id'];

    /** The four layers a student's look is built from, bottom to top, plus
     *  three more categories that live in this same table but aren't worn
     *  — Hub (a themed zone on My Learning Island, e.g. "CodeNova"),
     *  Building (a fixed structure belonging to one Hub, placed once by the
     *  admin) and Base (a purchasable item belonging to one Hub that each
     *  student places — and re-places — on their own island). They reuse
     *  this table because they need the same tools: an image upload and the
     *  pos_x/pos_y/scale/rotation placement editor. price_coins is unused
     *  for Hub/Building but very much used for Base. See sectionEnabled() —
     *  'avatar' is the base character — every student always has exactly
     *  one; the rest are optional overlays. 'accessory' is the only worn
     *  one a student can have several of at once. */
    public const CATEGORIES = [
        'avatar'    => 'Outfit',
        'outfit'    => 'Clothing Layer',
        'hat'       => 'Hat',
        'accessory' => 'Accessory',
        'base'      => 'Base',
        'hub'       => 'Hub',
        'building'  => 'Building',
    ];

    /** Categories whose placement editor previews against the island
     *  banner instead of the avatar stage. Hub/Building are admin-only —
     *  never gated, never shown on the student's Shop tab. Base is the odd
     *  one out: also placed against the island banner in Website Setup
     *  (for its default spot + fixed size), but it DOES sell in the
     *  student's Shop, and each student then drags their own copy around
     *  within its hub — see student_island_placements. */
    public const ISLAND_CATEGORIES = ['hub', 'building', 'base'];

    /** Which categories need a parent Hub picked (My Learning Island only
     *  makes sense to place a Building or a Base inside a themed zone). */
    public const HUB_CHILD_CATEGORIES = ['building', 'base'];

    /** Starting placement for a brand-new item of each category — a rough
     *  "about where this usually sits" so the admin only fine-tunes rather
     *  than positioning from scratch. A base character always fills the
     *  whole stage. Hub/Building/Base placements are percentages across the
     *  island banner, not the avatar stage — much smaller by default since
     *  that canvas is a wide scene, not a square close-up. */
    public const DEFAULT_PLACEMENT = [
        'avatar'    => ['pos_x' => 50, 'pos_y' => 50, 'scale' => 100, 'rotation' => 0],
        'outfit'    => ['pos_x' => 50, 'pos_y' => 58, 'scale' => 60,  'rotation' => 0],
        'hat'       => ['pos_x' => 50, 'pos_y' => 22, 'scale' => 40,  'rotation' => 0],
        'accessory' => ['pos_x' => 50, 'pos_y' => 45, 'scale' => 30,  'rotation' => 0],
        'base'      => ['pos_x' => 50, 'pos_y' => 65, 'scale' => 8,   'rotation' => 0],
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

    /** A Building or Base's Hub. */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'id');
    }

    /** A Hub's Buildings and Bases together. */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'id')->orderBy('sort_order');
    }

    /** The Program a Hub represents, for enrollment-based gating. Only set
     *  on category='hub' rows. */
    public function program(): BelongsTo
    {
        return $this->belongsTo(\App\Models\WebsiteSetup\Program::class, 'program_id', 'id');
    }

    /** Shop-tab categories (the ones a student can browse/buy from),
     *  in the school's chosen order, with the school's chosen labels —
     *  falling back to the built-in label and declaration order when
     *  nothing has been customised in Website Setup. */
    public const SHOP_CATEGORIES = ['avatar', 'outfit', 'hat', 'accessory', 'base'];

    public static function shopTabs(): array
    {
        $overrides = json_decode((string) setting('avatar_tab_labels'), true) ?: [];

        $tabs = [];
        foreach (self::SHOP_CATEGORIES as $i => $key) {
            $tabs[] = [
                'key'   => $key,
                'label' => $overrides[$key]['label'] ?? self::CATEGORIES[$key],
                'order' => (int) ($overrides[$key]['order'] ?? $i),
            ];
        }

        usort($tabs, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $tabs;
    }
}
