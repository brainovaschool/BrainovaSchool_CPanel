<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\SliderInterface;
use App\Models\Slider;
use App\Models\SliderTranslate;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class SliderRepository implements SliderInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $slider;
    private $slider_trans;

    public function __construct(Slider $slider, SliderTranslate $slider_trans)
    {
        $this->slider       = $slider;
        $this->slider_trans = $slider_trans;
    }

    public function all()
    {
        return $this->slider->active()->get();
    }

    public function getAll()
    {
        return $this->slider->orderBy('serial')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row              = new $this->slider;
            $row->name        = $request->name;
            $row->upload_id   = $this->UploadImageCreate($request->image, 'backend/uploads/sliders');
            $row->description = $request->description;
            $row->status      = $request->status;
            $row->serial      = $request->serial;
            $row->save();

            $en_row              = new $this->slider_trans;
            $en_row->slider_id   = $row->id;
            $en_row->locale      = request()->locale ?? config('app.locale');
            $en_row->name        = $request->name;
            $en_row->description = $request->description;
            $en_row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->slider->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row              = $this->slider->findOrfail($id);
            $row->name        = $request->name;
            $row->upload_id   = $this->UploadImageUpdate($request->image, 'backend/uploads/sliders', $row->upload_id);
            $row->description = $request->description;
            $row->status      = $request->status;
            $row->serial      = $request->serial;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    "name"        => $request->name,
                    "description" => $request->description,
                ]);
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->slider_trans->where('slider_id', $id)->delete();
            $slider     = $this->show($id);

            foreach ($request->name as $key => $name) {
                $row              = new $this->slider_trans;
                $row->slider_id   = $id;
                $row->locale      = $key;
                $row->name        = $name;
                $row->description = isset($request->description[$key]) ? $request->description[$key] : $slider->description;
                $row->save();
            }

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
            $row = $this->slider->find($id);
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
            $this->slider->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($slider_id)
    {
        return $this->slider_trans->where('slider_id', $slider_id)->get()->groupBy('locale');
    }
}