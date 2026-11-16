<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClassActivity;
use App\Services\B2Storage;

/** Mirrors ProgramClassMaterialController exactly - no role/guard check
 *  on purpose, the signed-URL middleware is the access control. */
class ProgramClassActivityController extends Controller
{
    public function download($activity, B2Storage $storage)
    {
        $row = ProgramClassActivity::find($activity);
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

        if ($row->link_url) {
            return redirect()->away($row->link_url);
        }

        abort(404);
    }
}
