<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Every existing responsibility defaults to active, so this never hides
 *  anything an admin is already relying on. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_responsibilities', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_responsibilities', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('freq');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_responsibilities', function (Blueprint $table) {
            if (Schema::hasColumn('portal_responsibilities', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
