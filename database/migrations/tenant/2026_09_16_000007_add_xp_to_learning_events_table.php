<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('learning_events', 'xp')) {
            return;
        }

        Schema::table('learning_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('xp')->default(0)->after('payload');
        });
    }

    public function down()
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropColumn('xp');
        });
    }
};
