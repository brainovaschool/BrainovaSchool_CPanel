<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('homework_quiz_questions') || Schema::hasColumn('homework_quiz_questions', 'skill_id')) {
            return;
        }

        Schema::table('homework_quiz_questions', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('homework_id')->constrained('skills')->nullOnDelete();
        });
    }

    public function down()
    {
        if (Schema::hasTable('homework_quiz_questions') && Schema::hasColumn('homework_quiz_questions', 'skill_id')) {
            Schema::table('homework_quiz_questions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('skill_id');
            });
        }
    }
};
