<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('reel_scenes', 'is_first_scene')) {
            return;
        }

        Schema::table('reel_scenes', function (Blueprint $table) {
            $table->boolean('is_first_scene')->default(false)->after('description')->comment('opens with the reel hook, spoken by Kea');
            $table->boolean('is_last_scene')->default(false)->after('is_first_scene')->comment('always closes with Brainbot: Think Smart / Kea: Learn Smarter');
        });
    }

    public function down()
    {
        Schema::table('reel_scenes', function (Blueprint $table) {
            $table->dropColumn(['is_first_scene', 'is_last_scene']);
        });
    }
};
