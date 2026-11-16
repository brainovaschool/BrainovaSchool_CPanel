<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A quiz question row now belongs to EITHER a Homework task (homework_id,
 *  as before) OR a Program Class lesson (program_class_id, new) - never
 *  both. Same sibling-column convention program_classes itself already
 *  uses for quiz_title/project_title/worksheet_title/assignment_title
 *  rather than a new table per owner type. homework_id has to become
 *  nullable for a Program-Class-owned row to exist at all; every
 *  existing row keeps its homework_id set exactly as it is today. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('homework_quiz_questions')) {
            return;
        }

        if (!Schema::hasColumn('homework_quiz_questions', 'program_class_id')) {
            Schema::table('homework_quiz_questions', function (Blueprint $table) {
                $table->foreignId('program_class_id')->nullable()->after('homework_id')
                    ->constrained('program_classes')->cascadeOnDelete();
            });
        }

        Schema::table('homework_quiz_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('homework_id')->nullable()->change();
        });
    }

    public function down()
    {
        // homework_id is deliberately left nullable - reverting it to
        // NOT NULL would fail outright if any Program-Class-owned row
        // (homework_id = NULL) still exists.
        if (Schema::hasColumn('homework_quiz_questions', 'program_class_id')) {
            Schema::table('homework_quiz_questions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('program_class_id');
            });
        }
    }
};
