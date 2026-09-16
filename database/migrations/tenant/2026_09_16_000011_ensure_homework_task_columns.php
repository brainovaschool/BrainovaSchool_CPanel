<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Like homework_quiz_questions, these columns are actively used by both the
 * real homework-creation flow (HomeworkRepository::store()) and the custom
 * quiz feature, but were never added through a migration — only directly on
 * the live database. Brings them into version control without disturbing
 * anything: each column is skipped if it already exists.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('homework', function (Blueprint $table) {
            if (!Schema::hasColumn('homework', 'title')) {
                $table->string('title')->nullable()->after('subject_id');
            }
            if (!Schema::hasColumn('homework', 'topic')) {
                $table->string('topic')->nullable()->after('title');
            }
            if (!Schema::hasColumn('homework', 'task_type')) {
                $table->string('task_type', 20)->default('homework')->after('topic');
            }
        });
    }

    public function down()
    {
        // Never drop columns this migration didn't create.
    }
};
