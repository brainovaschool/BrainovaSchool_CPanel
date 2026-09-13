<?php

namespace App\Repositories\Academic;

use App\Enums\Settings;
use App\Models\Academic\Shift;
use App\Models\ShiftTranslate;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Interfaces\Academic\ShiftInterface;

class ShiftRepository implements ShiftInterface
{
    use ReturnFormatTrait;
    private $shift;
    private $shiftTrans;

    public function __construct(Shift $shift , ShiftTranslate $shiftTrans)
    {
        $this->shift = $shift;
        $this->shiftTrans = $shiftTrans;
    }

    public function all()
    {
        return $this->shift->active()->get();
    }

    public function getAll()
    {
        return $this->shift->latest()->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        try {
            $shiftStore              = new $this->shift;
            $shiftStore->name        = $request->name;
            $shiftStore->status      = $request->status;
            $shiftStore->save();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->shift->find($id);
    }

    public function update($request, $id)
    {
        try {
            $shiftUpdate              = $this->shift->findOrfail($id);
            $shiftUpdate->name        = $request->name;
            $shiftUpdate->status      = $request->status;
            $shiftUpdate->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $shiftDestroy = $this->shift->find($id);
            $shiftDestroy->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
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



    public function translates($id){

        return $this->shiftTrans->where('shift_id',$id)->get()->groupBy('locale');
    }

    public function translateUpdate($request, $id){
        DB::beginTransaction();
        try {
            $delete_old = $this->shiftTrans->where('shift_id',$id)->delete();
            foreach($request->name as $key => $name){
                $row                   = new $this->shiftTrans;
                $row->shift_id        = $id ;
                $row->locale           = $key ;
                $row->name             = $name;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}
