<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('reel_projects', 'format')) {
            return;
        }

        Schema::table('reel_projects', function (Blueprint $table) {
            $table->string('format', 20)->default('vertical')->after('topic')->comment('vertical (9:16) or horizontal (16:9)');
            $table->text('hook')->nullable()->after('format')->comment('opening line, read by Kea in the first scene');
            $table->text('description')->nullable()->after('hook')->comment('replaces the old free-text topic field going forward');
            $table->unsignedInteger('target_duration_seconds')->nullable()->after('description')->comment('whole-reel target length, used as an AI generation hint');
            $table->json('character_ids')->nullable()->after('target_duration_seconds')->comment('characters involved in this reel, for AI scene generation');
        });
    }

    public function down()
    {
        Schema::table('reel_projects', function (Blueprint $table) {
            $table->dropColumn(['format', 'hook', 'description', 'target_duration_seconds', 'character_ids']);
        });
    }
};
