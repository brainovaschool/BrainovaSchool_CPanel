<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('question_banks', 'skill_id')) {
            return;
        }

        Schema::table('question_banks', function (Blueprint $table) {
            $table->foreignId('skill_id')->nullable()->after('question_group_id')->constrained('skills')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('question_banks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('skill_id');
        });
    }
};
