<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Costume choice moved from "pick one per reel" to "match the reel's
 *  own Theme field automatically" - each costume now carries the Theme
 *  value it belongs to (e.g. "Mint"), so the per-reel costume picker
 *  added by the previous migration (character_costumes on portal_reels)
 *  is no longer needed. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('reel_character_costumes', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_character_costumes', 'theme_value')) {
                $table->string('theme_value')->nullable()->after('label')
                    ->comment('matches a reel\'s Theme custom field value, e.g. "Mint" - which costume to auto-pick for that theme');
            }
        });

        Schema::table('portal_reels', function (Blueprint $table) {
            if (Schema::hasColumn('portal_reels', 'character_costumes')) {
                $table->dropColumn('character_costumes');
            }
        });
    }

    public function down()
    {
        Schema::table('reel_character_costumes', function (Blueprint $table) {
            $table->dropColumn('theme_value');
        });
        Schema::table('portal_reels', function (Blueprint $table) {
            $table->json('character_costumes')->nullable();
        });
    }
};
