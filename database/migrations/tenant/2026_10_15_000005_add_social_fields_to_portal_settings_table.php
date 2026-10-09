<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Social board settings (phase 8) — additive columns on the same
 *  portal_settings row used since phase 2, same reasoning as the phase 4
 *  work-log columns: one settings row for the whole portal. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_settings', 'social_platforms')) {
                $table->json('social_platforms')->nullable();
            }
            if (!Schema::hasColumn('portal_settings', 'social_metrics')) {
                $table->json('social_metrics')->nullable();
            }
            if (!Schema::hasColumn('portal_settings', 'reel_categories')) {
                $table->json('reel_categories')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            $table->dropColumn(['social_platforms', 'social_metrics', 'reel_categories']);
        });
    }
};
