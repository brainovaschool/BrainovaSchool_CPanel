<?php

namespace App\Repositories\WebsiteSetup;

use App\Enums\Settings;
use App\Models\WebsiteSetup\ProgramWaitlistEntry;

class ProgramWaitlistRepository
{
    private $model;

    public function __construct(ProgramWaitlistEntry $model)
    {
        $this->model = $model;
    }

    public function all()
    {
        return $this->model->with('category')->orderBy('id', 'desc')->paginate(Settings::PAGINATE);
    }

    public function destroy($id)
    {
        $row = $this->model->find($id);
        if (!$row) {
            return ['status' => false, 'message' => ___('common.something_went_wrong')];
        }
        $row->delete();
        return ['status' => true, 'message' => ___('alert.deleted')];
    }
}
