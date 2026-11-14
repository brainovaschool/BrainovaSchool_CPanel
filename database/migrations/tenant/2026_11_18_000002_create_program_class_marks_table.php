<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per (lesson, gradable item type, student) - marks is null until
 *  a teacher actually grades it, so "graded" vs "not graded yet" is a
 *  real, queryable state rather than guessed from a zero. Scoped to the
 *  Program's own enrolled students (student_program_enrollments), not an
 *  academic Class/Section/Subject - that's the whole point of this being
 *  separate from the Homework tab. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('program_class_marks')) {
            Schema::create('program_class_marks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('program_class_id')->constrained('program_classes')->cascadeOnDelete();
                $table->string('assessment_type', 20);
                // quiz | project | worksheet | assignment
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->decimal('marks', 6, 2)->nullable();
                $table->foreignId('graded_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamp('graded_at')->nullable();
                $table->timestamps();

                $table->unique(['program_class_id', 'assessment_type', 'student_id'], 'program_class_marks_unique');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('program_class_marks');
    }
};
