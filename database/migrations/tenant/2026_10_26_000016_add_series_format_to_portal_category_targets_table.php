<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Targets move from "per Pillar" to "per Series + Format" - the old
 *  `category`/`carousel_target` columns can't be dropped without the
 *  doctrine/dbal package this app doesn't have installed, so they're left
 *  in place unused (same as owner_staff_id on portal_page_fixes); `category`
 *  still gets a harmless "Series — Format" value on save purely to satisfy
 *  its old NOT NULL + unique constraint. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_category_targets', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_category_targets', 'series_name')) {
                $table->string('series_name')->nullable()->after('category');
            }
            if (!Schema::hasColumn('portal_category_targets', 'format')) {
                $table->string('format')->nullable()->after('series_name');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_category_targets', function (Blueprint $table) {
            $table->dropColumn(['series_name', 'format']);
        });
    }
};
