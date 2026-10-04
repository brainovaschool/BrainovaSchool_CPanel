<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Generalises the one-off "island_visible_to_all" / "island_tester_
 *  student_ids" Settings pair (built for just Learning Island) into a
 *  reusable per-student, per-feature access system — so Avatar, Learning
 *  Island, AI Helper, and anything added later can each be switched on
 *  for every student or just a hand-picked list of testers, independently
 *  of each other. See App\Models\LearningEngine\FeatureAccess. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('feature_access')) {
            Schema::create('feature_access', function (Blueprint $table) {
                $table->id();
                $table->string('feature_key', 60)->unique();
                $table->boolean('visible_to_all')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('feature_access_students')) {
            Schema::create('feature_access_students', function (Blueprint $table) {
                $table->id();
                $table->string('feature_key', 60);
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['feature_key', 'student_id']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('feature_access_students');
        Schema::dropIfExists('feature_access');
    }
};
