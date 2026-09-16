<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('student_skill_masteries', 'milestone_seen_at')) {
            return;
        }

        Schema::table('student_skill_masteries', function (Blueprint $table) {
            $table->timestamp('milestone_seen_at')->nullable()->after('mastered_at');
        });
    }

    public function down()
    {
        Schema::table('student_skill_masteries', function (Blueprint $table) {
            $table->dropColumn('milestone_seen_at');
        });
    }
};
