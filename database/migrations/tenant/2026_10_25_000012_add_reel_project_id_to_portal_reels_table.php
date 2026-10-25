<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links a reel/topic suggestion to its Reel Studio project (scenes +
 *  AI prompt generation) - the two were built as separate systems with
 *  no link between them; this is the bridge. Created lazily the first
 *  time an approved reel's "Open" is used to go into setup, never
 *  automatically for a still-"suggested" one. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'reel_project_id')) {
                $table->unsignedBigInteger('reel_project_id')->nullable()->after('id');
                $table->index('reel_project_id');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (Schema::hasColumn('portal_reels', 'reel_project_id')) {
                $table->dropColumn('reel_project_id');
            }
        });
    }
};
