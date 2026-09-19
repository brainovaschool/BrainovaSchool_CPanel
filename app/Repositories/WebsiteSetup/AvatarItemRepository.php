<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\LearningEngine\AvatarItem;
use App\Traits\ReturnFormatTrait;
use Illuminate\Support\Str;

class AvatarItemRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(AvatarItem $model)
    {
        $this->model = $model;
    }

    public function getByCategory(string $category)
    {
        return $this->model->category($category)->orderBy('sort_order')->paginate(\App\Enums\Settings::PAGINATE);
    }

    public function show($id)
    {
        return $this->model->find($id);
    }

    public function store($request)
    {
        try {
            $row              = new $this->model;
            $row->category    = $request->category;
            $row->name        = $request->name;
            $row->price_coins = (int) $request->price_coins;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;
            $row->image       = $this->uploadImage($request);
            $this->applyPlacement($row, $request);
            $row->save();

            return $this->responseWithSuccess(___('alert.created_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function update($request, $id)
    {
        try {
            $row              = $this->model->findOrFail($id);
            $row->category    = $request->category;
            $row->name        = $request->name;
            $row->price_coins = (int) $request->price_coins;
            $row->sort_order  = (int) $request->sort_order;
            $row->status      = $request->status;

            $image = $this->uploadImage($request);
            if ($image) {
                $row->image = $image;
            }

            $this->applyPlacement($row, $request);
            $row->save();

            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function destroy($id)
    {
        try {
            $this->model->findOrFail($id)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkDestroy(array $ids)
    {
        try {
            $this->model->whereIn('id', $ids)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    public function bulkStatus(array $ids, int $status)
    {
        try {
            $this->model->whereIn('id', $ids)->update(['status' => $status]);
            return $this->responseWithSuccess(___('alert.updated_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }

    /** Clamped so a stray value can never push a layer off the avatar
     *  entirely or blow it up past the stage. */
    private function applyPlacement($row, $request): void
    {
        $fallback = AvatarItem::DEFAULT_PLACEMENT[$request->category] ?? AvatarItem::DEFAULT_PLACEMENT['accessory'];

        $row->pos_x    = min(150, max(-50, (float) $request->input('pos_x', $fallback['pos_x'])));
        $row->pos_y    = min(150, max(-50, (float) $request->input('pos_y', $fallback['pos_y'])));
        $row->scale    = min(300, max(1, (float) $request->input('scale', $fallback['scale'])));
        $row->rotation = min(180, max(-180, (int) $request->input('rotation', $fallback['rotation'])));
    }

    private function uploadImage($request): ?string
    {
        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return null;
        }

        $path      = 'backend/uploads/avatar-items';
        $file      = $request->file('image');
        $extension = $file->guessExtension();
        $filename  = Str::random(6) . '_' . time() . '.' . $extension;

        if (setting('file_system') == 's3') {
            return s3Upload($path, $file);
        }

        $file->move($path, $filename);
        return $path . '/' . $filename;
    }
}
