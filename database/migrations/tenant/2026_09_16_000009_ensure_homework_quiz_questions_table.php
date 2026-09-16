<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The homework quiz feature (Custom/HomeworkQuizController) has been reading
 * and writing this table in production for a while, but no migration ever
 * created it — it exists only because someone created it directly on the
 * live database. This brings it into version control without disturbing
 * anything: skipped entirely wherever the table already exists.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('homework_quiz_questions')) {
            return;
        }

        Schema::create('homework_quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('homework_id')->constrained('homework')->cascadeOnDelete();
            $table->text('question')->nullable();
            $table->string('option_a')->nullable();
            $table->string('option_b')->nullable();
            $table->string('option_c')->nullable();
            $table->string('option_d')->nullable();
            $table->string('correct_answer')->nullable();
            $table->text('hint')->nullable();
            $table->text('explanation')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        // Never drop a table this migration didn't create.
    }
};
