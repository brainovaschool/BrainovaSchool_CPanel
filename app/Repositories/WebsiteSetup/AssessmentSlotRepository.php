<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\AssessmentLead;
use App\Models\WebsiteSetup\AssessmentSlot;
use App\Traits\ReturnFormatTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AssessmentSlotRepository
{
    use ReturnFormatTrait;

    private $slot;

    public function __construct(AssessmentSlot $slot)
    {
        $this->slot = $slot;
    }

    /**
     * The form only asks for a start time (24-hour HH:MM, from the
     * native time picker) - every meeting is a fixed 15 minutes, so
     * the end time is always start + 15, stored in the same "h:i A"
     * display format every other slot already uses (getTimeLabelAttribute,
     * the public booking calendar) rather than the raw 24-hour value.
     */
    private function timesFor(string $rawStart): array
    {
        $start = Carbon::createFromFormat('H:i', $rawStart);
        return [$start->format('g:i A'), $start->copy()->addMinutes(15)->format('g:i A')];
    }

    /**
     * $filters (all optional): from_date/to_date (the "day" filter - a
     * single day is just the same date in both), time_from/time_to
     * (24-hour "HH:MM" from a native time input - the "time" filter,
     * compared by actual time of day, not the stored string, see
     * timeRange() below), status (0/1), availability ('open' =
     * remaining > 0, 'full' = remaining <= 0 - the "slots" filter,
     * since "status" above already covers active/inactive and would
     * otherwise be confusingly overloaded).
     */
    public function getAll(array $filters = [])
    {
        $query = $this->slot->newQuery();

        if (!empty($filters['from_date'])) {
            $query->whereDate('slot_date', '>=', $filters['from_date']);
        }
        if (!empty($filters['to_date'])) {
            $query->whereDate('slot_date', '<=', $filters['to_date']);
        }
        if (!empty($filters['time_from'])) {
            $this->timeRange($query, '>=', $filters['time_from']);
        }
        if (!empty($filters['time_to'])) {
            $this->timeRange($query, '<=', $filters['time_to']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        if (!empty($filters['availability']) && $filters['availability'] === 'open') {
            $query->whereColumn('booked_count', '<', 'capacity');
        } elseif (!empty($filters['availability']) && $filters['availability'] === 'full') {
            $query->whereColumn('booked_count', '>=', 'capacity');
        }

        return $query->orderBy('slot_date', 'desc')->orderBy('start_time')
            ->paginate(Settings::PAGINATE)->withQueryString();
    }

    /**
     * start_time is stored as a "g:i A" display string (e.g. "9:30 AM"),
     * which sorts and compares wrong as plain text ("10:00 AM" < "9:00
     * AM" alphabetically) - STR_TO_DATE parses both sides back into an
     * actual time of day first, so "from 09:00 to 12:00" compares the
     * way a person means it, regardless of AM/PM wording or digit count.
     */
    private function timeRange($query, string $operator, string $rawTime24h): void
    {
        try {
            $label = Carbon::createFromFormat('H:i', $rawTime24h)->format('g:i A');
        } catch (\Throwable $e) {
            return; // not a real time - ignore rather than error the whole list out
        }

        $query->whereRaw("STR_TO_DATE(start_time, '%h:%i %p') {$operator} STR_TO_DATE(?, '%h:%i %p')", [$label]);
    }

    public function show($id)
    {
        return $this->slot->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            [$start, $end] = $this->timesFor($request->start_time);
            $row               = new $this->slot;
            $row->slot_date    = $request->slot_date;
            $row->start_time   = $start;
            $row->end_time     = $end;
            $row->capacity     = max(1, (int) $request->capacity);
            $row->note         = $request->note;
            $row->status       = $request->status;
            $row->save();
            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            [$start, $end] = $this->timesFor($request->start_time);
            $row               = $this->slot->findOrFail($id);
            $row->slot_date    = $request->slot_date;
            $row->start_time   = $start;
            $row->end_time     = $end;
            $row->capacity     = max($row->booked_count, max(1, (int) $request->capacity));
            $row->note         = $request->note;
            $row->status       = $request->status;
            $row->save();
            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $slot = $this->slot->findOrFail($id);
            // assessment_leads.assessment_slot_id is a real foreign key
            // (no cascade) - deleting a booked slot would otherwise fail
            // at the database level with a generic error, giving no clue
            // that it's booked at all. A slot that already has a family's
            // booking on it is deactivated, not deleted, to keep that
            // booking record intact.
            if ($slot->leads()->exists()) {
                DB::rollBack();
                return $this->responseWithError('This slot already has a booking on it, so it can\'t be deleted. Deactivate it instead if you want it to stop accepting new bookings.', []);
            }
            $slot->delete();
            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        try {
            $booked = AssessmentLead::whereIn('assessment_slot_id', $ids)->pluck('assessment_slot_id')->unique()->values()->all();
            $deletable = array_values(array_diff($ids, $booked));
            if ($deletable) {
                $this->slot->whereIn('id', $deletable)->delete();
            }
            if ($booked) {
                $msg = $deletable
                    ? count($deletable).' slot(s) deleted. '.count($booked).' slot(s) were skipped because they already have a booking on them - deactivate those instead if you want them to stop accepting new bookings.'
                    : 'None of the selected slots could be deleted because they all already have a booking on them. Deactivate them instead if you want them to stop accepting new bookings.';
                return $this->responseWithError($msg, []);
            }
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->slot->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /**
     * One row per (matching date x time) combination across the date
     * range, skipping any combination that already exists (same date +
     * same start time) so running this again over an overlapping range
     * never creates duplicates - safe to re-run with a wider range to
     * "top up" a week that's already partly filled in.
     */
    public function bulkGenerate($request): array
    {
        $start = Carbon::parse($request->start_date)->startOfDay();
        $end = Carbon::parse($request->end_date)->startOfDay();
        $weekdays = array_map('intval', (array) $request->input('weekdays', []));
        $times = collect(preg_split('/[\r\n,]+/', (string) $request->times))
            ->map(fn ($t) => trim($t))->filter()->unique()->values();
        $capacity = max(1, (int) $request->capacity);
        $status = (int) $request->status;
        $note = $request->note;

        $created = 0;
        $skipped = 0;
        $badTimes = [];
        $cursor = $start->copy();

        // A generous but finite cap - a typo in the date range (e.g. a
        // wrong year) should fail loudly instead of silently generating
        // years of slots.
        $maxDays = 370;
        $daysWalked = 0;

        while ($cursor->lte($end) && $daysWalked < $maxDays) {
            if (in_array($cursor->dayOfWeek, $weekdays, true)) {
                foreach ($times as $rawTime) {
                    try {
                        [$startLabel, $endLabel] = $this->timesFor($rawTime);
                    } catch (\Throwable $e) {
                        $badTimes[$rawTime] = true;
                        continue;
                    }

                    $exists = $this->slot->where('slot_date', $cursor->toDateString())
                        ->where('start_time', $startLabel)->exists();
                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $row = new $this->slot;
                    $row->slot_date = $cursor->toDateString();
                    $row->start_time = $startLabel;
                    $row->end_time = $endLabel;
                    $row->capacity = $capacity;
                    $row->note = $note;
                    $row->status = $status;
                    $row->save();
                    $created++;
                }
            }
            $cursor->addDay();
            $daysWalked++;
        }

        return ['created' => $created, 'skipped' => $skipped, 'badTimes' => array_keys($badTimes)];
    }

    /**
     * One click, no selection needed: removes every slot whose date and
     * time have already passed. A past slot still referenced by a lead
     * (the normal case - a slot with 0 left always has at least one) is
     * not just silently blocked the way a single/bulk delete is -
     * instead each such lead is detached first (assessment_slot_id set
     * to null) since its booked_slot_label already preserves what to
     * show for it afterwards (see MeetingBookingController::store()),
     * so the slot itself can be deleted without losing that history.
     * Deliberately only reaches dates that have already happened,
     * whether or not they're fully booked - a merely-expired slot with
     * seats still unused is exactly as unbookable from here on as a
     * fully-booked one, so there's no reason to keep either around.
     */
    public function cleanupPast(): array
    {
        $candidates = $this->slot->get()->filter(function ($slot) {
            try {
                $start = Carbon::createFromFormat('Y-m-d g:i A', $slot->slot_date->format('Y-m-d').' '.$slot->start_time);
            } catch (\Throwable $e) {
                return false; // unreadable time - leave it for a human to sort out
            }

            return $start->isPast();
        });

        if ($candidates->isEmpty()) {
            return ['deleted' => 0];
        }

        $byId = $candidates->keyBy('id');
        $ids = $byId->keys()->all();

        // A lead booked before booked_slot_label existed has none saved
        // yet - backfill it here, from the very slot about to be
        // deleted, so even an old booking keeps its date/time visible
        // afterwards instead of just going blank.
        AssessmentLead::whereIn('assessment_slot_id', $ids)->whereNull('booked_slot_label')->get()
            ->each(function ($lead) use ($byId) {
                $slot = $byId->get($lead->assessment_slot_id);
                $lead->booked_slot_label = $slot->slot_date->format('D, d M Y').' · '.$slot->time_label;
                $lead->save();
            });

        AssessmentLead::whereIn('assessment_slot_id', $ids)->update(['assessment_slot_id' => null]);
        $this->slot->whereIn('id', $ids)->delete();

        return ['deleted' => count($ids)];
    }

    /**
     * Applies a new capacity and/or status to every selected slot -
     * capacity is still clamped to at least that slot's own
     * booked_count (same guard update() uses), so a bulk edit can never
     * shrink a slot below the bookings it already has.
     */
    public function bulkEditFields(array $ids, ?string $capacity, ?string $status): array
    {
        $hasCapacity = $capacity !== null && $capacity !== '';
        $hasStatus = $status !== null && $status !== '';

        if (!$hasCapacity && !$hasStatus) {
            return $this->responseWithError('Nothing to update - set a new capacity and/or status first.', []);
        }

        try {
            $rows = $this->slot->whereIn('id', $ids)->get();
            foreach ($rows as $row) {
                if ($hasCapacity) {
                    $row->capacity = max($row->booked_count, max(1, (int) $capacity));
                }
                if ($hasStatus) {
                    $row->status = (int) $status;
                }
                $row->save();
            }
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}
