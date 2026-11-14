<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which of the public Programs (Website Setup > Course Catalogue > Program)
 * a staff member actually teaches - the same idea as
 * student_program_enrollments, for the other side of a course. Set from
 * the Program edit form's new "Teachers" field. A staff member can teach
 * several programs at once.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('staff_program_enrollments')) {
            return;
        }

        Schema::create('staff_program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['staff_id', 'program_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_program_enrollments');
    }
};
