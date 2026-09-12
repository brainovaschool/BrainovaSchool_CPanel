<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\TrialSlot;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class TrialSlotRepository
{
    use ReturnFormatTrait;

    private $slot;

    public function __construct(TrialSlot $slot)
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
            $this->slot->findOrFail($id)->delete();
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
            $this->slot->whereIn('id', $ids)->delete();
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
