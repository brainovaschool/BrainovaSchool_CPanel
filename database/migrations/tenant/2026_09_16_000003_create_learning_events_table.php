<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('learning_events')) {
            return;
        }

        Schema::create('learning_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->string('event_type', 40)->comment('lesson_started, lesson_completed, answer_submitted, hint_used, quest_claimed');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('learning_events');
    }
};
