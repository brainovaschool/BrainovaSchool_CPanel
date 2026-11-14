<?php

use App\Models\Upload;
use App\Models\WebsiteSetup\ProgramCategory;
use Illuminate\Database\Migrations\Migration;

/** Undoes 2026_11_14_000003: those three mascot character cutouts were
 *  sized for a small card thumbnail, but a category's image is actually
 *  a wide 1920x700 banner - the wrong shape entirely, so they'd either
 *  crop off the character or float tiny in a lot of empty space.
 *  Matches by the upload's own path rather than category name, since
 *  one of those categories has since been renamed (Academic Support ->
 *  Core Academics) - only clears it if still exactly what that
 *  migration set, so an admin's own manual change since then is left
 *  alone. */
return new class extends Migration
{
    private array $paths = [
        'frontend/img/categories/academic-support.png',
        'frontend/img/categories/beyond-screens.png',
        'frontend/img/categories/social-clubs.png',
    ];

    public function up()
    {
        $uploadIds = Upload::whereIn('path', $this->paths)->pluck('id');

        ProgramCategory::whereIn('upload_id', $uploadIds)->update(['upload_id' => null]);
    }

    public function down()
    {
        //
    }
};
