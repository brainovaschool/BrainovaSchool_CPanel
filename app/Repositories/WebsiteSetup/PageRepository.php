<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Interfaces\WebsiteSetup\PageInterface;
use App\Models\PageTranslate;
use App\Models\WebsiteSetup\Page;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PageRepository implements PageInterface
{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $page;
    private $page_trans;

    public function __construct(Page $page, PageTranslate $page_trans)
    {
        $this->page       = $page;
        $this->page_trans = $page_trans;
    }

    public function all()
    {
        return $this->page->get();
    }

    public function getAll()
    {
        return $this->page->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {

        DB::beginTransaction();
        try {
            $row                = new $this->page;
            $row->name          = $request->name;
            $row->slug          = Str::slug($request->name);
            $row->content       = $request->content;
            $row->menu_show     = $request->menu_show;
            $row->active_status = $request->active_status;
            $row->save();

            $en_row          = new $this->page_trans;
            $en_row->page_id = $row->id;
            $en_row->locale  = request()->locale ?? config('app.locale');
            $en_row->name    = $request->name;
            $en_row->content = $request->content;
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
        return $this->page->find($id);
    }

    public function findBySlug($slug)
    {
        return $this->page->where('slug', $slug)->where('active_status', 1)->firstOrFail();
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row                = $this->page->findOrfail($id);
            $row->name          = $request->name;
            $row->slug          = Str::slug($request->name);
            $row->content       = $request->content;
            $row->menu_show     = $request->menu_show;
            $row->active_status = $request->active_status;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    "name"    => $request->name,
                    "content" => $request->content,
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
            $row = $this->page->find($id);
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

    /** Pages use "active_status", not "status" — matches this repo's own store()/update(). */
    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->page->whereIn('id', $ids)->update(['active_status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translates($page_id)
    {
        return $this->page_trans->where('page_id', $page_id)->get()->groupBy('locale');
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->page_trans->where('page_id', $id)->delete();
            $slider     = $this->show($id);

            foreach ($request->name as $key => $name) {
                $row          = new $this->page_trans;
                $row->page_id = $id;
                $row->locale  = $key;
                $row->name    = $name;
                $row->content = isset($request->content[$key]) ? $request->content[$key] : $slider->content;
                $row->save();
            }

            DB::commit();
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}