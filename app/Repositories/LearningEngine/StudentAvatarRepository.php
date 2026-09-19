<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\AvatarItem;
use App\Models\LearningEngine\StudentAvatarProfile;
use App\Models\LearningEngine\StudentAvatarPurchase;
use App\Models\LearningEngine\StudentAvatarEquippedAccessory;
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

    public function __construct(LearningEventRepository $events)
    {
        $this->events = $events;
    }

    public function getOrCreateProfile(int $studentId): StudentAvatarProfile
    {
        return StudentAvatarProfile::firstOrCreate(
            ['student_id' => $studentId],
            ['voice_preset' => 'cheerful']
        );
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

    /** The student's current look as an ordered list of image paths, bottom
     *  layer first — body, then outfit, then every worn accessory, then the
     *  hat on top. Empty layers (nothing equipped, or an item with no image
     *  uploaded yet) are simply skipped. Used to composite the avatar both
     *  on the Avatar World page and the dashboard's speaking-avatar card. */
    public function equippedLayers(int $studentId): array
    {
        $profile = $this->getOrCreateProfile($studentId)->load(['avatar', 'outfit', 'hat']);

        $accessories = AvatarItem::active()
            ->whereIn('id', $this->equippedAccessoryIds($studentId))
            ->orderBy('sort_order')
            ->get();

        $layers = [];
        if ($profile->avatar && $profile->avatar->image) {
            $layers[] = $profile->avatar->image;
        }
        if ($profile->outfit && $profile->outfit->image) {
            $layers[] = $profile->outfit->image;
        }
        foreach ($accessories as $accessory) {
            if ($accessory->image) {
                $layers[] = $accessory->image;
            }
        }
        if ($profile->hat && $profile->hat->image) {
            $layers[] = $profile->hat->image;
        }

        return $layers;
    }

    /** Everything the Avatar World page needs in one call. */
    public function forStudent(int $studentId): array
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
            'voices'              => self::VOICE_PRESETS,
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

        if ($this->availableCoins($studentId) < $item->price_coins) {
            return $this->responseWithError("You don't have enough coins for this yet — keep earning XP!", []);
        }

        StudentAvatarPurchase::create([
            'student_id'     => $studentId,
            'avatar_item_id' => $item->id,
            'price_paid'     => $item->price_coins,
        ]);

        return $this->responseWithSuccess("You got it! \"{$item->name}\" is now yours.", []);
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
}
