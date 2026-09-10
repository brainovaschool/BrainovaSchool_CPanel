<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('news', 'type')) {
            Schema::table('news', function (Blueprint $table) {
                $table->string('type', 20)->default('news')->after('title')->comment('news | blog');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('news', 'type')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }
    }
};
