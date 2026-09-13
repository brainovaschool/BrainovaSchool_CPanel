<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\CounterInterface;
use App\Models\Counter;
use App\Models\CounterTranslate;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class CounterRepository implements CounterInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $counter;
    private $counter_trans;

    public function __construct(Counter $counter, CounterTranslate $counter_trans)
    {
        $this->counter       = $counter;
        $this->counter_trans = $counter_trans;
    }

    public function all()
    {
        return $this->counter->active()->get();
    }

    public function getAll()
    {
        return $this->counter->orderBy('serial')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row              = new $this->counter;
            $row->name        = $request->name;
            $row->total_count = $request->total_count;
            $row->upload_id   = $this->UploadImageCreate($request->image, 'backend/uploads/counters');
            $row->status      = $request->status;
            $row->serial      = $request->serial;
            $row->save();

            $en_row              = new $this->counter_trans;
            $en_row->counter_id  = $row->id;
            $en_row->locale      = request()->locale ?? config('app.locale');
            $en_row->name        = $request->name;
            $en_row->total_count = $request->total_count;
            $en_row->serial      = $request->serial;
            $en_row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->counter->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row              = $this->counter->findOrfail($id);
            $row->name        = $request->name;
            $row->total_count = $request->total_count;
            $row->upload_id   = $this->UploadImageUpdate($request->image, 'backend/uploads/counters', $row->upload_id);
            $row->status      = $request->status;
            $row->serial      = $request->serial;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    'name'        => $request->name,
                    'total_count' => $request->total_count,
                    'serial'      => $request->serial,
                ]);
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = $this->counter->find($id);
            $this->UploadImageDelete($row->upload_id);
            $row->delete();

            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids): int
    {
        $deleted = 0;
        foreach ($ids as $id) {
            if ($this->destroy($id)['status']) {
                $deleted++;
            }
        }
        return $deleted;
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->counter->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->counter_trans->where('counter_id', $id)->delete();
            $slider     = $this->show($id);

            foreach ($request->name as $key => $name) {
                $row              = new $this->counter_trans;
                $row->counter_id  = $id;
                $row->locale      = $key;
                $row->name        = $name;
                $row->total_count = isset($request->total_count[$key]) ? $request->total_count[$key] : $slider->total_count;
                $row->serial      = isset($request->serial[$key]) ? $request->serial[$key] : $slider->serial;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($counter_id)
    {
        return $this->counter_trans->where('counter_id', $counter_id)->get()->groupBy('locale');
    }
}