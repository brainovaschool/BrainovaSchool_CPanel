<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets the About page's "Why Brainova?" (key=statement) section show a
 *  YouTube video instead of its static image — kept as its own column
 *  rather than folded into `data`, since `data` on that section is
 *  already a structured Mission/Vision repeater (see SectionsRepository
 *  ::update()) and mixing the two risks corrupting that shape. */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('page_sections', 'video_url')) {
            return;
        }

        Schema::table('page_sections', function (Blueprint $table) {
            $table->string('video_url', 500)->nullable()->after('upload_id');
        });
    }

    public function down()
    {
        Schema::table('page_sections', function (Blueprint $table) {
            $table->dropColumn('video_url');
        });
    }
};
