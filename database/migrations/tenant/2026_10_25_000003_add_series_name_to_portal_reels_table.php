<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'series_name')) {
                $table->string('series_name')->nullable()->after('category');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (Schema::hasColumn('portal_reels', 'series_name')) {
                $table->dropColumn('series_name');
            }
        });
    }
};
