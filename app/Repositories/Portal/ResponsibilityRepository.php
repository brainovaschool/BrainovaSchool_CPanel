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
        return PortalResponsibility::with('owners')->orderByRaw('`key` IS NULL')->orderBy('title')->get();
    }

    public function forStaff(int $staffId)
    {
        return PortalResponsibility::with('owners')->whereHas('owners', fn ($q) => $q->where('staff.id', $staffId))->orderBy('title')->get();
    }

    public function store($request): array
    {
        $duty = PortalResponsibility::create([
            'title'       => $request->title,
            'description' => $request->description,
            'freq'        => $request->freq === 'weekly' ? 'weekly' : 'daily',
            // key stays null — only the one-off seeder creates the two
            // built-in (content, audience) duties.
        ]);

        $ownerIds = array_values(array_filter((array) $request->owner_staff_ids));
        if ($ownerIds) {
            $duty->owners()->sync($ownerIds);
            foreach ($duty->owners()->get() as $owner) {
                if ($owner->user_id) {
                    PortalNotifier::send($owner->user_id, 'New responsibility', "You're now responsible for \"{$duty->title}\".");
                }
            }
        }

        return $this->responseWithSuccess('Responsibility added.', []);
    }

    /** Also the transfer action — spec: "Admin can transfer any
     *  responsibility; the new and previous person are notified;
     *  permissions move with it." The admin can now pick any number of
     *  owners at once ("1 person, two or more"); whoever is added or
     *  dropped compared to before gets notified. */
    public function update(int $id, $request): array
    {
        $duty = PortalResponsibility::with('owners')->find($id);
        if (!$duty) {
            return $this->responseWithError(___('alert.not_found'), []);
        }

        $previousOwnerIds = $duty->owners->pluck('id')->all();

        $duty->title       = $request->title;
        $duty->description = $request->description;
        $duty->freq        = $request->freq === 'weekly' ? 'weekly' : 'daily';
        $duty->save();

        $newOwnerIds = array_values(array_filter((array) $request->owner_staff_ids));
        $duty->owners()->sync($newOwnerIds);

        $added   = array_diff($newOwnerIds, $previousOwnerIds);
        $removed = array_diff($previousOwnerIds, $newOwnerIds);

        if ($added || $removed) {
            \App\Models\Staff\Staff::whereIn('id', array_merge($added, $removed))->get()->each(function ($staff) use ($added, $duty) {
                if (!$staff->user_id) {
                    return;
                }
                if (in_array($staff->id, $added)) {
                    PortalNotifier::send($staff->user_id, 'New responsibility', "You're now responsible for \"{$duty->title}\".");
                } else {
                    PortalNotifier::send($staff->user_id, 'Responsibility moved', "You're no longer responsible for \"{$duty->title}\".");
                }
            });
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
        $duty = PortalResponsibility::with('owners')->find($id);
        if (!$duty) {
            return $this->responseWithError(___('alert.not_found'), []);
        }
        if ($staffId !== null && !$duty->owners->contains('id', $staffId)) {
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
