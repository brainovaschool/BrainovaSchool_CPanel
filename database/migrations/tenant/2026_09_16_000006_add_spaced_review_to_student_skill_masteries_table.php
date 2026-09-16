<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('student_skill_masteries', 'next_review_at')) {
            return;
        }

        Schema::table('student_skill_masteries', function (Blueprint $table) {
            $table->timestamp('next_review_at')->nullable()->after('milestone_seen_at');
            $table->unsignedSmallInteger('review_interval_days')->nullable()->after('next_review_at');
        });
    }

    public function down()
    {
        Schema::table('student_skill_masteries', function (Blueprint $table) {
            $table->dropColumn(['next_review_at', 'review_interval_days']);
        });
    }
};
