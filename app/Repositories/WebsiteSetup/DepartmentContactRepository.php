<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\DepartmentContactInterface;
use App\Models\DepartmentContactTranslate;
use App\Models\WebsiteSetup\DepartmentContact;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class DepartmentContactRepository implements DepartmentContactInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $depContact;
    private $departmentcontact_trans;

    public function __construct(DepartmentContact $depContact, DepartmentContactTranslate $departmentcontact_trans)
    {
        $this->depContact              = $depContact;
        $this->departmentcontact_trans = $departmentcontact_trans;
    }

    public function all()
    {
        return $this->depContact->active()->get();
    }

    public function getAll()
    {
        return $this->depContact->orderBy('id', 'desc')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row            = new $this->depContact;
            $row->name      = $request->name;
            $row->phone     = $request->phone;
            $row->email     = $request->email;
            $row->upload_id = $this->UploadImageCreate($request->image, 'backend/uploads/contact_info');
            $row->status    = $request->status;
            $row->save();

            $en_row                        = new $this->departmentcontact_trans;
            $en_row->department_contact_id = $row->id;
            $en_row->locale                = request()->locale ?? config('app.locale');
            $en_row->name                  = $request->name;
            $en_row->phone                 = $request->phone;
            $en_row->email                 = $request->email;
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
        return $this->depContact->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row            = $this->depContact->findOrfail($id);
            $row->name      = $request->name;
            $row->phone     = $request->phone;
            $row->email     = $request->email;
            $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/contact_info', $row->upload_id);
            $row->status    = $request->status;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    'name'  => $request->name,
                    'phone' => $request->phone,
                    'email' => $request->email,
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
            $row = $this->depContact->find($id);
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
            $this->depContact->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->departmentcontact_trans->where('department_contact_id', $id)->delete();
            $slider     = $this->show($id);

            foreach ($request->name as $key => $name) {
                $row                        = new $this->departmentcontact_trans;
                $row->department_contact_id = $id;
                $row->locale                = $key;
                $row->name                  = $name;
                $row->phone                 = isset($request->phone[$key]) ? $request->phone[$key] : $slider->phone;
                $row->email                 = isset($request->email[$key]) ? $request->email[$key] : $slider->email;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($department_contact_id)
    {
        return $this->departmentcontact_trans->where('department_contact_id', $department_contact_id)->get()->groupBy('locale');
    }
}