<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Final-asset links a teacher adds once a reel is actually produced.
 *  Canva link stays here only. Drive link also gets copied onto the
 *  linked portal_reels row (if any) when saved, since that's the column
 *  the Reels list and the Production/Upload Plan calendars already read. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('reel_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_projects', 'canva_link')) {
                $table->string('canva_link')->nullable()->after('cover_upload_id');
            }
            if (!Schema::hasColumn('reel_projects', 'drive_link')) {
                $table->string('drive_link')->nullable()->after('canva_link');
            }
        });
    }

    public function down()
    {
        Schema::table('reel_projects', function (Blueprint $table) {
            $table->dropColumn(['canva_link', 'drive_link']);
        });
    }
};
