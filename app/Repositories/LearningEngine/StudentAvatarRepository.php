<?php

namespace App\Repositories\LearningEngine;

use App\Models\LearningEngine\AvatarItem;
use App\Models\LearningEngine\StudentAvatarProfile;
use App\Models\LearningEngine\StudentAvatarPurchase;
use App\Traits\ReturnFormatTrait;

/**
 * The Kea avatar shop: a student's chosen look, an optional accessory badge,
 * their avatar's name, and a voice preset — plus the Coins economy that pays
 * for it all. Coins are tracked entirely through LearningEventRepository's
 * ledger (coins earned) minus this repository's purchase rows (coins spent);
 * there's no separate balance to drift out of sync.
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

    /** Everything the "My Avatar" page needs in one call. */
    public function forStudent(int $studentId): array
    {
        $profile = $this->getOrCreateProfile($studentId);
        $owned   = $this->ownedItemIds($studentId);

        return [
            'profile'  => $profile,
            'coins'    => $this->availableCoins($studentId),
            'owned'    => $owned,
            'avatars'  => AvatarItem::active()->category('avatar')->orderBy('sort_order')->get(),
            'accessories' => AvatarItem::active()->category('accessory')->orderBy('sort_order')->get(),
            'voices'   => self::VOICE_PRESETS,
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

    public function selectAccessory(int $studentId, ?int $itemId): array
    {
        if ($itemId && !in_array($itemId, $this->ownedItemIds($studentId), true)) {
            return $this->responseWithError('You need to buy this accessory first.', []);
        }

        $profile = $this->getOrCreateProfile($studentId);
        $profile->accessory_item_id = $itemId;
        $profile->save();

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
