<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Class Content: what's actually happening in class, visible to
 *  students/parents once published — modules, each holding lessons,
 *  each lesson holding activities and learning outcomes. Review workflow
 *  lives entirely on the module (whole module moves through review
 *  together; lessons/activities/outcomes inherit it):
 *
 *    draft -> submitted -> coordinator_reviewed -> approved (visible)
 *                       \-> changes_requested -> (teacher edits) -> submitted
 *
 *  No separate review-history table by design — only the latest
 *  coordinator feedback is kept, not a full thread. A real audit trail
 *  can be added later without touching this shape if it's ever needed.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('class_content_modules')) {
            Schema::create('class_content_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
                $table->foreignId('classes_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();

                $table->string('review_status', 20)->default('draft');
                // draft | submitted | changes_requested | coordinator_reviewed | approved
                $table->foreignId('coordinator_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->text('coordinator_feedback')->nullable();
                $table->timestamp('coordinator_reviewed_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('class_content_lessons')) {
            Schema::create('class_content_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('module_id')->constrained('class_content_modules')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('video_url', 500)->nullable();
                $table->date('class_date')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('class_content_materials')) {
            Schema::create('class_content_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_id')->constrained('class_content_lessons')->cascadeOnDelete();
                $table->string('label');
                $table->string('url', 500);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('class_content_activities')) {
            Schema::create('class_content_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_id')->constrained('class_content_lessons')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('video_url', 500)->nullable();
                $table->string('link_url', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('class_content_outcomes')) {
            Schema::create('class_content_outcomes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lesson_id')->constrained('class_content_lessons')->cascadeOnDelete();
                $table->string('outcome_text', 500);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('class_content_outcomes');
        Schema::dropIfExists('class_content_activities');
        Schema::dropIfExists('class_content_materials');
        Schema::dropIfExists('class_content_lessons');
        Schema::dropIfExists('class_content_modules');
    }
};
