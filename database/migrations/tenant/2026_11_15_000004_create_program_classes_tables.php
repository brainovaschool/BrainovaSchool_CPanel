<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets a teacher add their course's real classes (materials, PPTs,
 *  activities, outcomes, a project, a quiz) directly under the Program
 *  itself - no Class/Section/Subject link required, unlike Class Content.
 *  Same review workflow as Class Content (teacher drafts -> submits ->
 *  coordinator approves or sends back), but flattened to one level - a
 *  "program class" plays the part Module + Lesson played together there.
 *  This is meant to eventually replace Class Content for program-based
 *  courses, so it's intentionally simpler: one reviewer (the Coordinator)
 *  rather than Coordinator-then-Admin.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('program_classes')) {
            Schema::create('program_classes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('video_url', 500)->nullable();
                $table->date('class_date')->nullable();

                $table->string('project_title')->nullable();
                $table->text('project_description')->nullable();
                $table->string('project_link_url', 500)->nullable();
                $table->string('quiz_title')->nullable();
                $table->text('quiz_description')->nullable();
                $table->string('quiz_link_url', 500)->nullable();

                $table->unsignedInteger('sort_order')->default(0);
                $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();

                $table->string('review_status', 20)->default('draft');
                // draft | submitted | changes_requested | approved
                $table->timestamp('submitted_at')->nullable();
                $table->foreignId('coordinator_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->text('coordinator_feedback')->nullable();
                $table->timestamp('coordinator_reviewed_at')->nullable();
                $table->foreignId('approved_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('approved_at')->nullable();

                $table->timestamps();
            });
        }

        if (!Schema::hasTable('program_class_materials')) {
            Schema::create('program_class_materials', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_class_id')->constrained('program_classes')->cascadeOnDelete();
                $table->string('label');
                $table->string('url', 500)->nullable();
                $table->string('storage_key', 500)->nullable();
                $table->string('original_filename')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('program_class_activities')) {
            Schema::create('program_class_activities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_class_id')->constrained('program_classes')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('video_url', 500)->nullable();
                $table->string('link_url', 500)->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('program_class_outcomes')) {
            Schema::create('program_class_outcomes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_class_id')->constrained('program_classes')->cascadeOnDelete();
                $table->string('outcome_text', 500);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('program_class_outcomes');
        Schema::dropIfExists('program_class_activities');
        Schema::dropIfExists('program_class_materials');
        Schema::dropIfExists('program_classes');
    }
};
