<?php

use App\Models\WebsiteSetup\PageSections;
use Illuminate\Database\Migrations\Migration;

/** Beyond Academics and Core Academics Overview no longer read these -
 *  both now use their own Program Category's tagline/hero_title/
 *  hero_subtitle/image (the same fields the public category page
 *  already uses), so there's one real place to edit each banner instead
 *  of two. These two rows would otherwise just sit in Website Setup >
 *  Sections doing nothing if edited. */
return new class extends Migration
{
    public function up()
    {
        PageSections::whereIn('key', ['beyond_academics_overview_hero', 'core_academics_overview_hero'])->delete();
    }

    public function down()
    {
        //
    }
};
