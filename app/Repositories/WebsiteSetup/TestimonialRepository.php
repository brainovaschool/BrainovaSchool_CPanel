<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\Testimonial;
use App\Traits\CommonHelperTrait;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Facades\DB;

class TestimonialRepository
{
    use ReturnFormatTrait;
    use CommonHelperTrait;

    private $testimonial;

    public function __construct(Testimonial $testimonial)
    {
        $this->testimonial = $testimonial;
    }

    public function getAll()
    {
        return $this->testimonial->orderBy('type')->orderBy('sort_order')->orderBy('id', 'desc')
            ->paginate(Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->testimonial->find($id);
    }

    public function store($request)
    {
        DB::beginTransaction();
        try {
            $row = new $this->testimonial;
            $this->fill($row, $request);

            if ($request->hasFile('image')) {
                $row->upload_id = $this->UploadImageCreate($request->image, 'backend/uploads/testimonials');
            }

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
            $row = $this->testimonial->findOrFail($id);
            $this->fill($row, $request);

            if ($request->hasFile('image')) {
                $row->upload_id = $this->UploadImageUpdate($request->image, 'backend/uploads/testimonials', $row->upload_id);
            }

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
            $row = $this->testimonial->findOrFail($id);
            $this->UploadImageDelete($row->upload_id);
            $row->delete();
            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        DB::beginTransaction();
        try {
            foreach ($this->testimonial->whereIn('id', $ids)->get() as $row) {
                $this->UploadImageDelete($row->upload_id);
                $row->delete();
            }
            DB::commit();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->testimonial->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    private function fill(Testimonial $row, $request): void
    {
        $row->type       = in_array($request->type, ['testimonial', 'review'], true) ? $request->type : 'testimonial';
        $row->name       = $request->name;
        $row->role       = $request->role;
        $row->quote      = $request->quote;
        $row->rating     = $request->rating ? min(5, max(1, (int) $request->rating)) : null;
        $row->image_url  = $request->image_url;
        $row->sort_order = (int) $request->sort_order;
        $row->status     = $request->status;
    }
}
