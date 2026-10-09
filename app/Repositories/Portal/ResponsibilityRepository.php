<?php

namespace App\Repositories\Portal;

use App\Models\Portal\PortalDutyTick;
use App\Models\Portal\PortalResponsibility;
use App\Services\Portal\PortalNotifier;
use App\Traits\ReturnFormatTrait;

/** Responsibilities (duties) — handoff spec phase 5. Independent of the
 *  Role system by design: a duty's owner can be any Staff member
 *  regardless of their role, and the admin can move it to someone else
 *  at any time without touching roles or permissions at all. */
class ResponsibilityRepository
{
    use ReturnFormatTrait;

    public function all()
    {
        return PortalResponsibility::with('owner')->orderByRaw('`key` IS NULL')->orderBy('title')->get();
    }

    public function forStaff(int $staffId)
    {
        return PortalResponsibility::with('owner')->where('owner_staff_id', $staffId)->orderBy('title')->get();
    }

    public function store($request): array
    {
        PortalResponsibility::create([
            'title'          => $request->title,
            'description'    => $request->description,
            'freq'           => $request->freq === 'weekly' ? 'weekly' : 'daily',
            'owner_staff_id' => $request->owner_staff_id ?: null,
            // key stays null — only the one-off seeder creates the two
            // built-in (content, audience) duties.
        ]);

        return $this->responseWithSuccess('Responsibility added.', []);
    }

    /** Also the transfer action — spec: "Admin can transfer any
     *  responsibility; the new and previous person are notified;
     *  permissions move with it." Moving IS just changing owner_staff_id,
     *  since any permission the duty carries is checked live against
     *  whoever currently owns it (PortalResponsibility::ownerUserIdForKey()). */
    public function update(int $id, $request): array
    {
        $duty = PortalResponsibility::with('owner')->find($id);
        if (!$duty) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $previousOwnerId = $duty->owner_staff_id;
        $previousOwnerUserId = $duty->owner->user_id ?? null;

        $duty->title       = $request->title;
        $duty->description = $request->description;
        $duty->freq        = $request->freq === 'weekly' ? 'weekly' : 'daily';
        $duty->owner_staff_id = $request->owner_staff_id ?: null;
        $duty->save();

        if ((int) $previousOwnerId !== (int) $duty->owner_staff_id) {
            $duty->load('owner');
            $newOwnerUserId = $duty->owner->user_id ?? null;

            if ($previousOwnerUserId) {
                PortalNotifier::send($previousOwnerUserId, 'Responsibility moved', "\"{$duty->title}\" has been moved to someone else.");
            }
            if ($newOwnerUserId) {
                PortalNotifier::send($newOwnerUserId, 'New responsibility', "You're now responsible for \"{$duty->title}\".");
            }
        }

        return $this->responseWithSuccess('Saved.', []);
    }

    public function destroy(int $id): array
    {
        $duty = PortalResponsibility::find($id);
        if (!$duty) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($duty->key) {
            return $this->responseWithError('Built-in responsibilities can\'t be deleted — reassign or leave it unowned instead.', []);
        }

        $duty->delete();
        return $this->responseWithSuccess('Removed.', []);
    }

    /** $staffId: the acting employee's own staff id, or null when a
     *  manager is ticking on someone's behalf (manager may tick any
     *  duty; an employee only their own — enforced by the caller). */
    public function tick(int $id, ?int $staffId, int $actingUserId): array
    {
        $duty = PortalResponsibility::find($id);
        if (!$duty) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($staffId !== null && (int) $duty->owner_staff_id !== $staffId) {
            return $this->responseWithError("This isn't your responsibility.", []);
        }

        $period = $duty->currentPeriod();
        if (PortalDutyTick::where('responsibility_id', $duty->id)->where('period', $period)->exists()) {
            return $this->responseWithSuccess('Already marked done for this period.', []);
        }

        PortalDutyTick::create([
            'responsibility_id' => $duty->id,
            'period'            => $period,
            'ticked_by'         => $actingUserId,
            'ticked_at'         => now(),
        ]);

        return $this->responseWithSuccess('Marked done.', []);
    }
}
