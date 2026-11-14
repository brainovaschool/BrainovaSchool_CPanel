<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClassMaterial;
use App\Services\B2Storage;

class ProgramClassMaterialController extends Controller
{
    /** No role/guard check here on purpose - mirrors ClassContentMaterialController.
     *  The signed-URL middleware is the access control; this just resolves
     *  the material to wherever its actual file lives. */
    public function download($material, B2Storage $storage)
    {
        $row = ProgramClassMaterial::find($material);
        if (!$row) {
            abort(404);
        }

        if ($row->storage_key) {
            $url = $storage->temporaryDownloadUrl($row->storage_key);
            if (!$url) {
                abort(503, 'File storage is temporarily unavailable.');
            }
            return redirect()->away($url);
        }

        if ($row->url) {
            return redirect()->away($row->url);
        }

        abort(404);
    }
}
