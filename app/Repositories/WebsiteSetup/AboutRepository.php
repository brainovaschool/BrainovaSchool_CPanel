<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use Illuminate\Support\Facades\DB;
use App\Interfaces\WebsiteSetup\AboutInterface;
use App\Models\AboutTranslate;
use App\Models\WebsiteSetup\About;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;

class AboutRepository implements AboutInterface{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $about;
    private $about_trans;

    public function __construct(About $about, AboutTranslate $about_trans)
    {
        $this->about = $about;
        $this->about_trans = $about_trans;
    }

    public function all()
    {
        return $this->about->active()->get();
    }

    public function getAll()
    {
        return $this->about->orderBy('serial')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row                   = new $this->about;
            $row->name             = $request->name;
            $row->upload_id        = $this->UploadImageCreate($request->image, 'backend/uploads/abouts');
            $row->icon_upload_id   = $this->UploadImageCreate($request->icon, 'backend/uploads/abouts');
            $row->description      = $request->description;
            $row->status           = $request->status;
            $row->serial           = $request->serial;
            $row->save();

            $en_row                   = new $this->about_trans;
            $en_row->about_id        = $row->id ;
            $en_row->locale           = request()->locale ?? config('app.locale') ;
            $en_row->name             = $request->name;
            $en_row->description      = $request->description;
            $en_row->save();

            DB::commit();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            dd($th);
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->about->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row                   = $this->about->findOrfail($id);
            $row->name             = $request->name;
            $row->upload_id        = $this->UploadImageUpdate($request->image, 'backend/uploads/abouts', $row->upload_id);
            $row->icon_upload_id   = $this->UploadImageUpdate($request->icon, 'backend/uploads/abouts', $row->icon_upload_id);
            $row->description      = $request->description;
            $row->status           = $request->status;
            $row->serial           = $request->serial;
            $row->save();

            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    "name"        => $request->name,
                    "description" => $request->description,
                ]);
            }
            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            info($th->getMessage());
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $row = $this->about->find($id);
            $this->UploadImageDelete($row->upload_id);
            $this->UploadImageDelete($row->icon_upload_id);
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
            $this->about->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->about_trans->where('about_id',$id)->delete();
            $slider = $this->show($id);

            foreach($request->name as $key => $name){
                $row                   = new $this->about_trans;
                $row->about_id        = $id ;
                $row->locale           = $key ;
                $row->name             = $name;
                $row->description      = isset($request->description[$key]) ? $request->description[$key] : $slider->description;
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

    public function translates($about_id)
    {
        return $this->about_trans->where('about_id',$about_id)->get()->groupBy('locale');
    }
}