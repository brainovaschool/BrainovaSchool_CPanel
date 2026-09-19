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

    /** Paginated far higher than the app default — these are small visual
     *  thumbnails, and splitting a wardrobe across pages of 10 made it look
     *  like there was a cap on how many items you could add. */
    public function getByCategory(string $category)
    {
        return $this->model->category($category)->orderBy('sort_order')->paginate(60);
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

    /** Creates one item per uploaded file, named after the file, priced and
     *  placed from the category defaults. Positioning is still per-item, but
     *  building a wardrobe no longer means repeating the whole form for
     *  every single hat. */
    public function bulkStore($request): array
    {
        $files = array_filter((array) $request->file('images', []));
        if (empty($files)) {
            return $this->responseWithError('Choose at least one image to upload.', []);
        }

        $placement = AvatarItem::DEFAULT_PLACEMENT[$request->category] ?? AvatarItem::DEFAULT_PLACEMENT['accessory'];
        $price     = max(0, (int) $request->input('price_coins', 0));
        $created   = 0;

        foreach ($files as $file) {
            if (!$file->isValid()) {
                continue;
            }

            try {
                $row              = new $this->model;
                $row->category    = $request->category;
                $row->name        = Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 57);
                $row->price_coins = $price;
                $row->sort_order  = 0;
                $row->status      = \App\Enums\Status::ACTIVE;
                $row->image       = $this->storeFile($file);
                $row->pos_x       = $placement['pos_x'];
                $row->pos_y       = $placement['pos_y'];
                $row->scale       = $placement['scale'];
                $row->rotation    = $placement['rotation'];
                $row->save();
                $created++;
            } catch (\Throwable $th) {
                report($th);
            }
        }

        if ($created === 0) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }

        return $this->responseWithSuccess(
            $created . ' item(s) added. Open each one to set where it sits on the avatar.',
            []
        );
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

        return $this->storeFile($request->file('image'));
    }

    private function storeFile($file): string
    {
        $path      = 'backend/uploads/avatar-items';
        $extension = $file->guessExtension();
        $filename  = Str::random(6) . '_' . time() . '_' . Str::random(4) . '.' . $extension;

        if (setting('file_system') == 's3') {
            return s3Upload($path, $file);
        }

        $file->move($path, $filename);
        return $path . '/' . $filename;
    }
}
