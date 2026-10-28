<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A short one-line label for what a scene is about - separate from the
 *  longer Description box, shown as that scene's own title bar. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('reel_scenes', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_scenes', 'title')) {
                $table->string('title')->nullable()->after('sort_order');
            }
        });
    }

    public function down()
    {
        Schema::table('reel_scenes', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }
};
