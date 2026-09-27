<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The simple per-student term tracker agreed on instead of a full academic
 * calendar: which of the 6 terms a student is on, and which story theme
 * they picked for it (chosen once, changed only when they finish a term —
 * enforced in the repository, not the database). Also where "today's
 * learning already happened" is stamped, which both the pet/tree care and
 * the learning-first shop/decoration gate read from — see
 * LearningEventRepository::record().
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('student_avatar_profiles', 'current_term')) {
            return;
        }

        Schema::table('student_avatar_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('current_term')->default(1)->after('island_pos_y');
            $table->string('theme', 30)->nullable()->after('current_term');
            $table->date('last_activity_date')->nullable()->after('theme');
        });
    }

    public function down()
    {
        Schema::table('student_avatar_profiles', function (Blueprint $table) {
            $table->dropColumn(['current_term', 'theme', 'last_activity_date']);
        });
    }
};
