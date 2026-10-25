<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            // Admin-configurable Reel formats, same pattern as reel_categories -
            // replaces the Reel/Carousel/Post list that used to be hardcoded in
            // the controller validation and views.
            if (!Schema::hasColumn('portal_settings', 'reel_formats')) {
                $table->json('reel_formats')->nullable();
            }
            // Optional series a reel/topic can belong to - no universal
            // default, the admin defines their own from a blank list.
            if (!Schema::hasColumn('portal_settings', 'series_names')) {
                $table->json('series_names')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            if (Schema::hasColumn('portal_settings', 'reel_formats')) {
                $table->dropColumn('reel_formats');
            }
            if (Schema::hasColumn('portal_settings', 'series_names')) {
                $table->dropColumn('series_names');
            }
        });
    }
};
