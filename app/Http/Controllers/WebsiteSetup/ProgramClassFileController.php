<?php

namespace App\Http\Controllers\WebsiteSetup;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetup\ProgramClass;
use App\Services\B2Storage;

/** Resolves a Project/Quiz/Worksheet/Assignment's uploaded file (or its
 *  plain pasted link, as a fallback) - mirrors ProgramClassMaterialController,
 *  just keyed by {id, type} instead of a child row, since these four are
 *  single fields directly on the ProgramClass itself. No role/guard check
 *  on purpose - the signed-URL middleware is the access control. */
class ProgramClassFileController extends Controller
{
    public function download(int $id, string $type, B2Storage $storage)
    {
        if (!in_array($type, ['project', 'quiz', 'worksheet', 'assignment'], true)) {
            abort(404);
        }

        $row = ProgramClass::find($id);
        if (!$row) {
            abort(404);
        }

        $storageKey = $row->{"{$type}_storage_key"};
        if ($storageKey) {
            $url = $storage->temporaryDownloadUrl($storageKey);
            if (!$url) {
                abort(503, 'File storage is temporarily unavailable.');
            }
            return redirect()->away($url);
        }

        $linkUrl = $row->{"{$type}_link_url"};
        if ($linkUrl) {
            return redirect()->away($linkUrl);
        }

        abort(404);
    }
}
