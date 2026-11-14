<?php

use App\Models\Upload;
use App\Models\WebsiteSetup\ProgramCategory;
use Illuminate\Database\Migrations\Migration;

/** Default images for the categories that have a clear fit among the
 *  mascot art provided - Academic Support (reading), Beyond Screens
 *  (tablet), Social Clubs (together). Homeschooling and Beyond Acedamics
 *  are left as the accent-gradient placeholder until a fitting image
 *  exists - admin can replace any of these any time from Website Setup
 *  > Course Catalogue > Program Category > Edit. */
return new class extends Migration
{
    private array $map = [
        'Academic Support' => 'frontend/img/categories/academic-support.png',
        'Beyond Screens'   => 'frontend/img/categories/beyond-screens.png',
        'Social Clubs'     => 'frontend/img/categories/social-clubs.png',
    ];

    public function up()
    {
        foreach ($this->map as $name => $path) {
            $category = ProgramCategory::where('name', $name)->whereNull('upload_id')->first();
            if (!$category) {
                continue;
            }

            $upload = new Upload();
            $upload->path = $path;
            $upload->save();

            $category->upload_id = $upload->id;
            $category->save();
        }
    }

    public function down()
    {
        //
    }
};
