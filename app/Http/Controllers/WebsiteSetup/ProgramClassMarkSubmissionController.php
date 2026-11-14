<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClassMark;
use App\Services\B2Storage;

class ProgramClassMarkSubmissionController extends Controller
{
    /** No role/guard check here on purpose - mirrors ProgramClassMaterialController.
     *  The signed-URL middleware is the access control; this just resolves
     *  the mark's submission to wherever its actual file lives. */
    public function download($mark, B2Storage $storage)
    {
        $row = ProgramClassMark::find($mark);
        if (!$row) {
            abort(404);
        }

        if ($row->submission_storage_key) {
            $url = $storage->temporaryDownloadUrl($row->submission_storage_key);
            if (!$url) {
                abort(503, 'File storage is temporarily unavailable.');
            }
            return redirect()->away($url);
        }

        if ($row->submission_link_url) {
            return redirect()->away($row->submission_link_url);
        }

        abort(404);
    }
}
