<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_skill_masteries')) {
            return;
        }

        Schema::create('student_skill_masteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->string('mastery_level', 20)->default('not_started')->comment('not_started, developing, proficient, advanced');
            $table->unsignedInteger('attempts_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->timestamp('last_practiced_at')->nullable();
            $table->timestamp('mastered_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'skill_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_skill_masteries');
    }
};
