<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\AvatarItem;
use App\Models\LearningEngine\StudentAvatarProfile;
use App\Models\LearningEngine\StudentAvatarPurchase;
use App\Models\LearningEngine\StudentAvatarEquippedAccessory;
use App\Models\LearningEngine\StudentIslandPlacement;
use App\Models\StudentInfo\StudentProgramEnrollment;
use App\Traits\ReturnFormatTrait;

/**
 * The avatar shop: a layered dress-up system. A student always has exactly
 * one base character (avatar_item_id), and may additionally have one outfit,
 * one hat, and any number of accessories worn at once — each layer is its
 * own transparent-PNG image, stacked bottom to top (see equippedLayers()) to
 * build the composited look shown on the dashboard and the Avatar World
 * page. Paid for with Coins, tracked entirely through LearningEventRepository's
 * ledger (coins earned) minus this repository's purchase rows (coins spent).
 */
class StudentAvatarRepository
{
    use ReturnFormatTrait;

    public const VOICE_PRESETS = [
        'cheerful' => ['label' => 'Cheerful', 'rate' => 1.05, 'pitch' => 1.3],
        'calm'     => ['label' => 'Calm',     'rate' => 0.9,  'pitch' => 0.95],
        'deep'     => ['label' => 'Deep',     'rate' => 0.9,  'pitch' => 0.7],
        'bright'   => ['label' => 'Bright',   'rate' => 1.15, 'pitch' => 1.45],
        'classic'  => ['label' => 'Classic',  'rate' => 0.98, 'pitch' => 1.15],
    ];

    private $events;
    private $missions;

    public function __construct(LearningEventRepository $events, StudentMissionRepository $missions)
    {
        $this->events   = $events;
        $this->missions = $missions;
    }

    public function getOrCreateProfile(int $studentId): StudentAvatarProfile
    {
        return StudentAvatarProfile::firstOrCreate(
            ['student_id' => $studentId],
            ['voice_preset' => 'cheerful']
        );
    }

    /** Learning-first, per the product plan's Section 11: shopping and
     *  decorating only open once today's real learning has happened —
     *  same signal the pet/tree care uses (LearningEventRepository::markTodayActive()),
     *  so there's exactly one definition of "did today's work" across the
     *  island, not two rules to keep in sync. */
    public function hasEarnedTodaysActivity(int $studentId): bool
    {
        $date = $this->getOrCreateProfile($studentId)->last_activity_date;

        return $date && \Carbon\Carbon::parse($date)->isToday();
    }

    /** Pet/tree mood — Section 9's gentle rule: a missed day never breaks
     *  anything, it just waits. Weekends don't count against the streak
     *  (holiday mode's first, simplest form — a real school-holiday
     *  calendar can extend this later if one gets built). Nothing here is
     *  stored; it's read fresh from last_activity_date every time. */
    public function petTreeStatus(int $studentId): array
    {
        $date = $this->getOrCreateProfile($studentId)->last_activity_date;

        if (!$date) {
            return ['mood' => 'sleepy', 'days_away' => null, 'earned_today' => false, 'holiday' => now()->isWeekend()];
        }

        $last = \Carbon\Carbon::parse($date)->startOfDay();
        $daysAway = 0;
        $cursor = $last->copy();
        while ($cursor->lt(now()->startOfDay())) {
            $cursor->addDay();
            if (!$cursor->isWeekend()) {
                $daysAway++;
            }
        }

        $mood = $daysAway === 0 ? 'happy' : ($daysAway <= 2 ? 'sleepy' : 'sad');

        return [
            'mood'         => $mood,
            'days_away'    => $daysAway,
            'earned_today' => $this->hasEarnedTodaysActivity($studentId),
            'holiday'      => now()->isWeekend(),
        ];
    }

    /** Free items (price 0) are owned by everyone automatically; anything
     *  else needs a purchase row. */
    public function ownedItemIds(int $studentId): array
    {
        $free      = AvatarItem::active()->where('price_coins', 0)->pluck('id')->all();
        $purchased = StudentAvatarPurchase::where('student_id', $studentId)->pluck('avatar_item_id')->all();

        return array_values(array_unique(array_merge($free, $purchased)));
    }

    public function availableCoins(int $studentId): int
    {
        $earned = $this->events->totalCoinsEarned($studentId);
        $spent  = (int) StudentAvatarPurchase::where('student_id', $studentId)->sum('price_paid');

        return max(0, $earned - $spent);
    }

    public function equippedAccessoryIds(int $studentId): array
    {
        return StudentAvatarEquippedAccessory::where('student_id', $studentId)->pluck('avatar_item_id')->all();
    }

    /** Hubs a student can't fully use yet because they aren't enrolled in
     *  the program that hub represents — a hub with no program picked in
     *  Website Setup is never locked. */
    public function lockedHubIds(int $studentId): array
    {
        $gatedHubs = AvatarItem::active()->category('hub')->whereNotNull('program_id')->get(['id', 'program_id']);
        if ($gatedHubs->isEmpty()) {
            return [];
        }

        $enrolled = StudentProgramEnrollment::where('student_id', $studentId)->pluck('program_id')->all();

        return $gatedHubs->filter(fn ($hub) => !in_array($hub->program_id, $enrolled, true))->pluck('id')->all();
    }

    /** Base items that live inside a locked hub — these still show in the
     *  shop (so the student can see what they're missing) but can't be
     *  bought until the hub unlocks. */
    public function lockedBaseItemIds(int $studentId): array
    {
        $locked = $this->lockedHubIds($studentId);
        if (empty($locked)) {
            return [];
        }

        return AvatarItem::active()->category('base')->whereIn('parent_id', $locked)->pluck('id')->all();
    }

    /** The student's current look as an ordered list of layers, bottom layer
     *  first — body, then outfit, then every worn accessory, then the hat on
     *  top. Each layer carries the placement set for that item in Website
     *  Setup (see the pos_x/pos_y/scale/rotation columns), which is what
     *  makes a standalone headband image land on the head instead of
     *  covering the whole character. Items with no image uploaded yet are
     *  skipped. Used to composite the avatar both on the Avatar World page
     *  and the dashboard's speaking-avatar card. */
    public function equippedLayers(int $studentId): array
    {
        $profile = $this->getOrCreateProfile($studentId)->load(['avatar', 'outfit', 'hat']);

        $accessories = AvatarItem::active()
            ->whereIn('id', $this->equippedAccessoryIds($studentId))
            ->orderBy('sort_order')
            ->get();

        // A layer whose section has been switched off in Website Setup stops
        // showing on the avatar, even if the student had it equipped before.
        $ordered = array_filter([$profile->avatar]);
        if ($profile->outfit && AvatarItem::sectionEnabled('outfit')) {
            $ordered[] = $profile->outfit;
        }
        if (AvatarItem::sectionEnabled('accessory')) {
            foreach ($accessories as $accessory) {
                $ordered[] = $accessory;
            }
        }
        if ($profile->hat && AvatarItem::sectionEnabled('hat')) {
            $ordered[] = $profile->hat;
        }

        $layers = [];
        foreach ($ordered as $item) {
            if (!$item->image) {
                continue;
            }

            $layers[] = [
                'image'    => $item->image,
                'x'        => $item->pos_x ?? 50,
                'y'        => $item->pos_y ?? 50,
                'scale'    => $item->scale ?? 100,
                'rotation' => $item->rotation ?? 0,
            ];
        }

        return $layers;
    }

    /** Everything the My Avatar page needs — character/outfit/hat/
     *  accessory selection and shopping. Nothing island-related, so a
     *  student with only Avatar access never has island data loaded at
     *  all, let alone shown. */
    public function forAvatarPage(int $studentId): array
    {
        $profile = $this->getOrCreateProfile($studentId);
        $owned   = $this->ownedItemIds($studentId);

        return [
            'profile'             => $profile,
            'coins'               => $this->availableCoins($studentId),
            'owned'               => $owned,
            'equippedAccessories' => $this->equippedAccessoryIds($studentId),
            'layers'              => $this->equippedLayers($studentId),
            'bodies'              => AvatarItem::active()->category('avatar')->orderBy('sort_order')->get(),
            'outfits'             => AvatarItem::active()->category('outfit')->orderBy('sort_order')->get(),
            'hats'                => AvatarItem::active()->category('hat')->orderBy('sort_order')->get(),
            'accessories'         => AvatarItem::active()->category('accessory')->orderBy('sort_order')->get(),
            'shopTabs'            => AvatarItem::shopTabs(AvatarItem::AVATAR_SHOP_CATEGORIES),
            'voices'              => self::VOICE_PRESETS,
        ];
    }

    /** Everything the My Learning Island page needs — the room/building/
     *  mission world and the Yard Decoration shop. Still shows the
     *  student's current look (read-only — 'layers') standing on the
     *  island, but never any selection UI for it; that lives on the
     *  separate My Avatar page. */
    public function forIslandPage(int $studentId): array
    {
        $profile = $this->getOrCreateProfile($studentId);
        $owned   = $this->ownedItemIds($studentId);

        return [
            'profile'             => $profile,
            'coins'               => $this->availableCoins($studentId),
            'owned'               => $owned,
            'layers'              => $this->equippedLayers($studentId),
            'bases'               => AvatarItem::active()->category('base')->with('parent')->orderBy('sort_order')->get(),
            'hubs'                => AvatarItem::active()->category('hub')->with(['children' => function ($q) {
                $q->active()->where('category', 'building')->orderBy('sort_order');
            }])->orderBy('sort_order')->get(),
            'lockedHubIds'        => $this->lockedHubIds($studentId),
            'lockedBaseIds'       => $this->lockedBaseItemIds($studentId),
            'placements'          => StudentIslandPlacement::where('student_id', $studentId)->get()->keyBy('avatar_item_id'),
            'shopTabs'            => AvatarItem::shopTabs(AvatarItem::ISLAND_SHOP_CATEGORIES),
            'petTree'             => $this->petTreeStatus($studentId),
            'approvedExamResults' => $this->missions->approvedExamResults($studentId),
        ];
    }

    public function purchase(int $studentId, int $itemId): array
    {
        $item = AvatarItem::active()->find($itemId);
        if (!$item) {
            return $this->responseWithError('That item could not be found.', []);
        }

        if (in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You already own this.', []);
        }

        if (!$this->hasEarnedTodaysActivity($studentId)) {
            return $this->responseWithError("Do today's learning first — then the shop opens up!", []);
        }

        if ($item->category === 'base' && $item->parent_id && in_array($item->parent_id, $this->lockedHubIds($studentId), true)) {
            return $this->responseWithError("You need to be enrolled in that hub's program to unlock this first.", []);
        }

        if ($this->availableCoins($studentId) < $item->price_coins) {
            return $this->responseWithError("You don't have enough coins for this yet — keep earning XP!", []);
        }

        StudentAvatarPurchase::create([
            'student_id'     => $studentId,
            'avatar_item_id' => $item->id,
            'price_paid'     => $item->price_coins,
        ]);

        // A base appears on the student's island the moment it's bought, at
        // the spot the admin set as its default — no separate "place it"
        // step needed before it shows up.
        if ($item->category === 'base') {
            StudentIslandPlacement::updateOrCreate(
                ['student_id' => $studentId, 'avatar_item_id' => $item->id],
                ['pos_x' => $item->pos_x, 'pos_y' => $item->pos_y]
            );
        }

        return $this->responseWithSuccess("You got it! \"{$item->name}\" is now yours.", []);
    }

    /** Keeps a dragged/nudged base item inside its own hub's area — a
     *  circle centred on the hub's own placement point, sized off the
     *  hub's own scale, so no separate zone-drawing tool is needed. */
    private function clampToHub(AvatarItem $item, float $x, float $y): array
    {
        $hub = $item->parent_id ? AvatarItem::active()->category('hub')->find($item->parent_id) : null;

        if (!$hub) {
            return [min(96, max(4, $x)), min(96, max(4, $y))];
        }

        $radius = max((float) $hub->scale * 1.6, 14.0);
        $dx     = $x - (float) $hub->pos_x;
        $dy     = $y - (float) $hub->pos_y;
        $dist   = sqrt($dx * $dx + $dy * $dy);

        if ($dist > $radius && $dist > 0) {
            $ratio = $radius / $dist;
            $x     = (float) $hub->pos_x + $dx * $ratio;
            $y     = (float) $hub->pos_y + $dy * $ratio;
        }

        return [min(99, max(1, $x)), min(99, max(1, $y))];
    }

    /** Saves where a student has dragged or nudged one of their owned bases
     *  to. Clamped server-side too, so a tampered request can't place it
     *  outside its hub. */
    public function placeItem(int $studentId, int $itemId, float $x, float $y): array
    {
        $item = AvatarItem::active()->category('base')->find($itemId);
        if (!$item || !in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You need to own this first.', []);
        }

        if (!$this->hasEarnedTodaysActivity($studentId)) {
            return $this->responseWithError("Do today's learning first — then you can decorate!", []);
        }

        [$x, $y] = $this->clampToHub($item, $x, $y);

        StudentIslandPlacement::updateOrCreate(
            ['student_id' => $studentId, 'avatar_item_id' => $itemId],
            ['pos_x' => $x, 'pos_y' => $y]
        );

        return $this->responseWithSuccess(___('alert.updated_successfully'), ['pos_x' => $x, 'pos_y' => $y]);
    }

    /** Saves where the student's own avatar is standing on the island —
     *  free to roam anywhere on the banner, not locked to any one hub. */
    public function placeAvatar(int $studentId, float $x, float $y): array
    {
        $profile = $this->getOrCreateProfile($studentId);
        $profile->island_pos_x = min(96, max(4, $x));
        $profile->island_pos_y = min(96, max(4, $y));
        $profile->save();

        return $this->responseWithSuccess(___('alert.updated_successfully'), [
            'pos_x' => $profile->island_pos_x,
            'pos_y' => $profile->island_pos_y,
        ]);
    }

    public function selectAvatar(int $studentId, int $itemId): array
    {
        if (!in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You need to buy this look first.', []);
        }

        $profile = $this->getOrCreateProfile($studentId);
        $profile->avatar_item_id = $itemId;
        $profile->save();

        return $this->responseWithSuccess(___('alert.updated_successfully'), []);
    }

    public function selectOutfit(int $studentId, ?int $itemId): array
    {
        if ($itemId && !in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You need to buy this outfit first.', []);
        }

        $profile = $this->getOrCreateProfile($studentId);
        $profile->outfit_item_id = $itemId;
        $profile->save();

        return $this->responseWithSuccess(___('alert.updated_successfully'), []);
    }

    public function selectHat(int $studentId, ?int $itemId): array
    {
        if ($itemId && !in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You need to buy this hat first.', []);
        }

        $profile = $this->getOrCreateProfile($studentId);
        $profile->hat_item_id = $itemId;
        $profile->save();

        return $this->responseWithSuccess(___('alert.updated_successfully'), []);
    }

    /** Accessories can be worn several at once, so each one is toggled
     *  independently rather than "selected" like the other slots. */
    public function toggleAccessory(int $studentId, int $itemId): array
    {
        if (!in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You need to buy this accessory first.', []);
        }

        $existing = StudentAvatarEquippedAccessory::where('student_id', $studentId)->where('avatar_item_id', $itemId)->first();

        if ($existing) {
            $existing->delete();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        }

        StudentAvatarEquippedAccessory::create(['student_id' => $studentId, 'avatar_item_id' => $itemId]);

        return $this->responseWithSuccess(___('alert.updated_successfully'), []);
    }

    public function saveProfile(int $studentId, ?string $name, string $voicePreset): array
    {
        if (!array_key_exists($voicePreset, self::VOICE_PRESETS)) {
            $voicePreset = 'cheerful';
        }

        $profile = $this->getOrCreateProfile($studentId);
        $profile->avatar_name  = $name ? trim(substr($name, 0, 40)) : null;
        $profile->voice_preset = $voicePreset;
        $profile->save();

        return $this->responseWithSuccess(___('alert.updated_successfully'), []);
    }

    /** A theme is picked once per term and stays fixed until the term ends
     *  (see StudentMissionRepository::maybeAdvanceTerm(), which is what
     *  clears it again) — so this refuses to overwrite one that's already
     *  set, rather than trusting the caller not to offer the picker twice. */
    public function chooseTheme(int $studentId, string $theme): array
    {
        if (!array_key_exists($theme, \App\Models\LearningEngine\Mission::THEMES)) {
            return $this->responseWithError('Not a real theme.', []);
        }

        $profile = $this->getOrCreateProfile($studentId);
        if ($profile->theme) {
            return $this->responseWithError('This term\'s theme is already chosen — it changes at the end of the term.', []);
        }

        $profile->theme = $theme;
        $profile->save();

        return $this->responseWithSuccess(___('alert.updated_successfully'), []);
    }
}
