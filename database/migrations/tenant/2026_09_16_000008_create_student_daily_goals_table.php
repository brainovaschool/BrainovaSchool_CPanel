<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_daily_goals')) {
            return;
        }

        Schema::create('student_daily_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('goal_date');
            $table->string('goal_key', 40);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'goal_date', 'goal_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_daily_goals');
    }
};
