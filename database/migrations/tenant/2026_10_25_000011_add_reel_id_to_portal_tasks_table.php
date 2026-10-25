<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links a Team Portal task back to the Content Pool reel it came from,
 *  so assigning a reel creates (or updates, on re-assign) one task
 *  instead of piling up a duplicate every time. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_tasks', 'reel_id')) {
                $table->unsignedBigInteger('reel_id')->nullable()->after('id');
                $table->index('reel_id');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('portal_tasks', 'reel_id')) {
                $table->dropColumn('reel_id');
            }
        });
    }
};
