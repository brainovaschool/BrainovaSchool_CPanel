<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which of the public Programs (Website Setup > Programs — Homeschooling,
 * Tutoring, Electives & Enrichment, Social Clubs) a student is actually
 * enrolled in. Didn't exist until now: the marketing program catalogue and
 * the student records were entirely disconnected, so a student's dashboard
 * had no way to show "Home Schooling + Junior Coding Explorers" even though
 * both existed as separate data. A student can hold several at once.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_program_enrollments')) {
            return;
        }

        Schema::create('student_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['student_id', 'program_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_program_enrollments');
    }
};
