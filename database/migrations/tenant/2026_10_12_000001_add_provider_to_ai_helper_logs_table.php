<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('ai_helper_logs', 'provider')) {
            return;
        }

        Schema::table('ai_helper_logs', function (Blueprint $table) {
            $table->string('provider', 20)->nullable()->after('tool')->comment('which AI account answered: gemini, openai, anthropic');
        });
    }

    public function down()
    {
        Schema::table('ai_helper_logs', function (Blueprint $table) {
            $table->dropColumn('provider');
        });
    }
};
