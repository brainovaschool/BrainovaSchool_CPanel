<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Where the student's own avatar is currently standing on My Learning
 *  Island. Null until they move it for the first time, at which point the
 *  page just centres it. */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('student_avatar_profiles', 'island_pos_x')) {
            return;
        }

        Schema::table('student_avatar_profiles', function (Blueprint $table) {
            $table->decimal('island_pos_x', 6, 2)->nullable()->after('voice_preset');
            $table->decimal('island_pos_y', 6, 2)->nullable()->after('island_pos_x');
        });
    }

    public function down()
    {
        Schema::table('student_avatar_profiles', function (Blueprint $table) {
            $table->dropColumn(['island_pos_x', 'island_pos_y']);
        });
    }
};
