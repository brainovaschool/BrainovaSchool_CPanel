<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Same sibling-column change as homework_quiz_questions, for the answers
 *  table. MySQL treats NULL as distinct in a unique index, so the
 *  existing (homework_id, student_id, question_id) index never protects
 *  Program-Class-owned rows (homework_id always NULL there) - a second,
 *  separate unique index covers those instead. The existing index is
 *  left completely alone. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('homework_quiz_answers')) {
            return;
        }

        if (!Schema::hasColumn('homework_quiz_answers', 'program_class_id')) {
            Schema::table('homework_quiz_answers', function (Blueprint $table) {
                $table->unsignedBigInteger('program_class_id')->nullable()->after('homework_id')->index();
            });
        }

        Schema::table('homework_quiz_answers', function (Blueprint $table) {
            $table->unsignedBigInteger('homework_id')->nullable()->change();
        });

        if (!$this->indexExists('homework_quiz_answers', 'pc_quiz_answers_pc_student_q_unique')) {
            Schema::table('homework_quiz_answers', function (Blueprint $table) {
                $table->unique(['program_class_id', 'student_id', 'question_id'], 'pc_quiz_answers_pc_student_q_unique');
            });
        }
    }

    public function down()
    {
        if ($this->indexExists('homework_quiz_answers', 'pc_quiz_answers_pc_student_q_unique')) {
            Schema::table('homework_quiz_answers', function (Blueprint $table) {
                $table->dropUnique('pc_quiz_answers_pc_student_q_unique');
            });
        }
        if (Schema::hasColumn('homework_quiz_answers', 'program_class_id')) {
            Schema::table('homework_quiz_answers', function (Blueprint $table) {
                $table->dropColumn('program_class_id');
            });
        }
    }

    /** Plain SHOW INDEX query - avoids doctrine/dbal, which this app
     *  doesn't have installed. MySQL-only, same as every other direct
     *  DB:: call already used throughout this quiz feature. */
    private function indexExists(string $table, string $indexName): bool
    {
        return !empty(DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]));
    }
};
