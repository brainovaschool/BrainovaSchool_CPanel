<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\ContactInfoInterface;
use App\Models\ContactInfoTranslate;
use App\Models\WebsiteSetup\ContactInfo;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class ContactInfoRepository implements ContactInfoInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $contactInfo;
    private $contactinfo_trans;

    public function __construct(ContactInfo $contactInfo, ContactInfoTranslate $contactinfo_trans)
    {
        $this->contactInfo       = $contactInfo;
        $this->contactinfo_trans = $contactinfo_trans;
    }

    public function all()
    {
        return $this->contactInfo->active()->get();
    }

    public function getAll()
    {
        return $this->contactInfo->orderBy('id', 'desc')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row            = new $this->contactInfo;
            $row->name      = $request->name;
            $row->address   = $request->address;
            $row->upload_id = $this->UploadImageCreate($request->image, 'backend/uploads/contact_info');
            $row->status    = $request->status;
            $row->save();

            $en_row                  = new $this->contactinfo_trans;
            $en_row->contact_info_id = $row->id;
            $en_row->locale          = request()->locale ?? config('app.locale');
            $en_row->name            = $request->name;
            $en_row->address         = $request->address;
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
        return $this->contactInfo->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row            = $this->contactInfo->findOrfail($id);
            $row->name      = $request->name;
            $row->address   = $request->address;
            $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/contact_info', $row->upload_id);
            $row->status    = $request->status;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    'name'    => $request->name,
                    'address' => $request->address,
                ]);
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
            $row = $this->contactInfo->find($id);
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
            $this->contactInfo->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->contactinfo_trans->where('contact_info_id', $id)->delete();
            $slider     = $this->show($id);

            foreach ($request->name as $key => $name) {
                $row                  = new $this->contactinfo_trans;
                $row->contact_info_id = $id;
                $row->locale          = $key;
                $row->name            = $name;
                $row->address         = isset($request->address[$key]) ? $request->address[$key] : $slider->address;
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

    public function translates($contact_info_id)
    {
        return $this->contactinfo_trans->where('contact_info_id', $contact_info_id)->get()->groupBy('locale');
    }
}