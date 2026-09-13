<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\News;
use App\Enums\Settings;
use Illuminate\Support\Facades\DB;
use App\Interfaces\WebsiteSetup\NewsInterface;
use App\Models\NewsTranslate;
use App\Traits\ReturnFormatTrait;
use App\Traits\CommonHelperTrait;

class NewsRepository implements NewsInterface{

    use ReturnFormatTrait;
    use CommonHelperTrait;
    private $news;
    private $news_trans;

    public function __construct(News $news, NewsTranslate $news_trans)
    {
        $this->news = $news;
        $this->news_trans = $news_trans;
    }

    public function all()
    {
        return $this->news->active()->get();
    }

    public function getAll()
    {
        return $this->news->orderBy('id', 'desc')->paginate(Settings::PAGINATE);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row                   = new $this->news;
            $row->title            = $request->title;
            $row->type             = in_array($request->type, ['news', 'blog'], true) ? $request->type : 'news';
            $row->description      = $request->description;
            $row->date             = $request->date;
            $row->publish_date     = $request->publish_date;
            $row->upload_id        = $this->UploadImageCreate($request->image, 'backend/uploads/news', $row->upload_id);
            $row->status           = $request->status;
            $row->save();

            $en_row                   = new $this->news_trans;
            $en_row->news_id        = $row->id ;
            $en_row->locale           = request()->locale ?? config('app.locale') ;
            $en_row->title             = $request->title;
            $en_row->description      = $request->description;
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
        return $this->news->find($id);
    }

    public function update($request, $id)
    {
        DB::beginTransaction();
        try {
            $row = $this->news->findOrfail($id);
            $row->title = $request->title;
            $row->type = in_array($request->type, ['news', 'blog'], true) ? $request->type : ($row->type ?: 'news');
            $row->description = $request->description;
            $row->date = $request->date;
            $row->publish_date = $request->publish_date;
            $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/news', $row->upload_id);
            $row->status = $request->status;
            $row->save();

            // Add translation update
            if ($row->defaultTranslate) {
                $row->defaultTranslate->update([
                    'title' => $request->title,
                    'description' => $request->description
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
            $row = $this->news->find($id);
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
            $this->news->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function translateUpdate($request, $id)
    {

        DB::beginTransaction();
        try {
            $delete_old = $this->news_trans->where('news_id',$id)->delete();
            $slider = $this->show($id);

            foreach($request->title as $key => $title){
                $row                   = new $this->news_trans;
                $row->news_id        = $id ;
                $row->locale           = $key ;
                $row->title             = $title;
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

    public function translates($news_id)
    {
        return $this->news_trans->where('news_id',$news_id)->get()->groupBy('locale');
    }
}