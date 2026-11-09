<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One generic table for every kind of per-student completion in Class
 *  Content - viewing the Lesson itself, an Activity (many per lesson), a
 *  lesson's Project, or its Quiz (at most one each) - rather than four
 *  near-identical tables. completable_type is 'lesson'/'project'/'quiz'
 *  (completable_id = class_content_lessons.id, since those three live on
 *  the lesson row itself) or 'activity' (completable_id =
 *  class_content_activities.id). A lesson's OVERALL "done" state for the
 *  dashboard is never stored here - it's derived (lesson row done AND
 *  every activity done AND project/quiz done if the lesson has them) -
 *  same reasoning as Yearly Goals' progress being derived from its tasks,
 *  not a second manually-kept flag.
 *
 *  `score` is reserved for a later real quiz integration - phase 1 only
 *  ever writes a plain "done" row (score stays null). `xp_awarded` lets
 *  phase 2 (coins/XP via LearningEventRepository::EVENT_LESSON_COMPLETED)
 *  record once it has actually paid out for this row, without a second
 *  table, and without ever double-paying a row phase 1 already created. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('class_content_completions')) {
            Schema::create('class_content_completions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->string('completable_type', 20); // activity | project | quiz
                $table->unsignedBigInteger('completable_id');
                $table->unsignedTinyInteger('score')->nullable();
                $table->boolean('xp_awarded')->default(false);
                $table->timestamp('completed_at');
                $table->timestamps();

                $table->unique(['student_id', 'completable_type', 'completable_id'], 'cc_completions_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('class_content_completions');
    }
};
