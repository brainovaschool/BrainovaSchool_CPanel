<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\AssessmentLead;
use App\Models\WebsiteSetup\AssessmentSlot;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class AssessmentSlotRepository
{
    use ReturnFormatTrait;

    private $slot;

    public function __construct(AssessmentSlot $slot)
    {
        $this->slot = $slot;
    }

    public function getAll()
    {
        return $this->slot->orderBy('slot_date', 'desc')->orderBy('start_time')
            ->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->slot->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row               = new $this->slot;
            $row->slot_date    = $request->slot_date;
            $row->start_time   = $request->start_time;
            $row->end_time     = $request->end_time;
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
            $row               = $this->slot->findOrFail($id);
            $row->slot_date    = $request->slot_date;
            $row->start_time   = $request->start_time;
            $row->end_time     = $request->end_time;
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
}
