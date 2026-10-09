<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Work Log phase (4) needs its own day-template settings — added here
 *  rather than in the original portal_settings migration since that one
 *  is already live; additive-only column adds, matching the standing
 *  rule that a migration may only ADD. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_settings', 'work_day_hours')) {
                $table->decimal('work_day_hours', 4, 1)->default(8);
            }
            if (!Schema::hasColumn('portal_settings', 'day_start')) {
                $table->string('day_start', 5)->default('09:00'); // HH:MM
            }
            if (!Schema::hasColumn('portal_settings', 'lunch_after_hours')) {
                $table->decimal('lunch_after_hours', 4, 1)->default(4);
            }
            if (!Schema::hasColumn('portal_settings', 'activities')) {
                $table->json('activities')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('portal_settings', function (Blueprint $table) {
            $table->dropColumn(['work_day_hours', 'day_start', 'lunch_after_hours', 'activities']);
        });
    }
};
