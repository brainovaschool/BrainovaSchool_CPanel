<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A short series code (e.g. "SS" for Speak Smarter) that, combined with a
 *  per-series running number, becomes each approved reel's own code (SS1,
 *  SS2, ...) - see the sibling migration adding `code` to portal_reels. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_reel_series', 'code')) {
            Schema::table('portal_reel_series', function (Blueprint $table) {
                $table->string('code', 20)->nullable()->after('name');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_reel_series', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
