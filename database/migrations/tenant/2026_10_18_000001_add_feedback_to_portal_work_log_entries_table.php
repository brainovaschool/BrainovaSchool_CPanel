<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_work_log_entries', function (Blueprint $table) {
            $table->text('feedback')->nullable()->after('notes');
        });
    }

    public function down()
    {
        Schema::table('portal_work_log_entries', function (Blueprint $table) {
            $table->dropColumn('feedback');
        });
    }
};
