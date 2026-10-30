<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every project's scene list now always brackets its numbered "content"
 *  scenes with a fixed Start Scene (the intro - what this video is about)
 *  and End Scene (the send-off, where the reel's End Hook gets spoken) -
 *  this column tells the three apart. Content scenes keep sort_order
 *  1..N among themselves; start is always sort_order 0, end is always
 *  the highest. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('reel_scenes', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_scenes', 'scene_type')) {
                $table->string('scene_type', 20)->default('content')->after('reel_project_id');
            }
        });
    }

    public function down()
    {
        Schema::table('reel_scenes', function (Blueprint $table) {
            $table->dropColumn('scene_type');
        });
    }
};
