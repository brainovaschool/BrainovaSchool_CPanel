<?php

namespace App\Repositories\WebsiteSetup;

use App\Models\WebsiteSetup\DashboardFeature;
use App\Traits\ReturnFormatTrait;

class DashboardFeatureRepository
{
    use ReturnFormatTrait;

    private $model;

    public function __construct(DashboardFeature $model)
    {
        $this->model = $model;
    }

    public function getByPortal(string $portal)
    {
        return $this->model->where('portal', $portal)->orderBy('sort_order')->get();
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

    public function bulkDestroy(array $ids)
    {
        try {
            $this->model->whereIn('id', $ids)->delete();
            return $this->responseWithSuccess(___('alert.deleted_successfully'), []);
        } catch (\Throwable $th) {
            return $this->responseWithError(___('alert.something_went_wrong_please_try_again'), []);
        }
    }
}
