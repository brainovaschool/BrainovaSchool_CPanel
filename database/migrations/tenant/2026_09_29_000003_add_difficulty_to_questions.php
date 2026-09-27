<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A plain 1/2/3 difficulty level per question, alongside the skill it's
 * already tagged with — the last piece needed for a question to carry
 * subject + grade + skill + difficulty, which the story/mission system
 * will eventually use to pick the right question for the right moment.
 * Nullable: existing questions simply have no difficulty set yet.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('question_banks', 'difficulty')) {
            Schema::table('question_banks', function (Blueprint $table) {
                $table->tinyInteger('difficulty')->nullable()->after('skill_id');
            });
        }

        if (!Schema::hasColumn('homework_quiz_questions', 'difficulty')) {
            Schema::table('homework_quiz_questions', function (Blueprint $table) {
                $table->tinyInteger('difficulty')->nullable()->after('skill_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('question_banks', 'difficulty')) {
            Schema::table('question_banks', function (Blueprint $table) {
                $table->dropColumn('difficulty');
            });
        }

        if (Schema::hasColumn('homework_quiz_questions', 'difficulty')) {
            Schema::table('homework_quiz_questions', function (Blueprint $table) {
                $table->dropColumn('difficulty');
            });
        }
    }
};
