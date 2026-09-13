<?php

namespace App\Repositories\Academic;

use App\Enums\Settings;
use App\Models\Academic\Section;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use App\Interfaces\Academic\SectionInterface;
use App\Models\ClassSectionTranslate;

class SectionRepository implements SectionInterface
{
    use ReturnFormatTrait;
    private $section;
    private $sectionTrans;

    public function __construct(Section $section , ClassSectionTranslate $sectionTrans)
    {
        $this->section = $section;
        $this->sectionTrans = $sectionTrans;
    }

    public function all()
    {
        return $this->section->active()->get();
    }

    public function getAll()
    {
        return $this->section->latest()->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        try {
            $sectionStore              = new $this->section;
            $sectionStore->name        = $request->name;
            $sectionStore->status      = $request->status;
            $sectionStore->save();
            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function show($id)
    {
        return $this->section->find($id);
    }

    public function update($request, $id)
    {
        try {
            $sectionUpdate              = $this->section->findOrfail($id);
            $sectionUpdate->name        = $request->name;
            $sectionUpdate->status      = $request->status;
            $sectionUpdate->save();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $sectionDestroy = $this->section->find($id);
            $sectionDestroy->delete();
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

        return $this->sectionTrans->where('section_id',$id)->get()->groupBy('locale');
    }

    public function translateUpdate($request, $id){
        DB::beginTransaction();
        try {
            $delete_old = $this->sectionTrans->where('section_id',$id)->delete();
            $section = $this->show($id);

            foreach($request->name as $key => $name){
                $row                   = new $this->sectionTrans;
                $row->section_id        = $id ;
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
