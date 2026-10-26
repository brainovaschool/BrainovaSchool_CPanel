<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Assigned automatically the first time a reel is approved (see
 *  SocialRepository::assignReelCode()) - the series' own code plus the
 *  next running number in that series, e.g. "SS1". Left null for reels
 *  whose series has no code set. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_reels', 'code')) {
            Schema::table('portal_reels', function (Blueprint $table) {
                $table->string('code', 20)->nullable()->after('series_name');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
