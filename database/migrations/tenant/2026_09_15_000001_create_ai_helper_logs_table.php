<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('ai_helper_logs')) {
            return;
        }

        Schema::create('ai_helper_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('user_role', 20)->comment('teacher or student');
            $table->string('tool', 40)->comment('lesson_plan_text, lesson_plan_visual, lesson_plan_slides, student_question');
            $table->text('summary')->comment('what was asked, for admin visibility');
            $table->timestamps();

            $table->index(['user_id', 'user_role', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('ai_helper_logs');
    }
};
