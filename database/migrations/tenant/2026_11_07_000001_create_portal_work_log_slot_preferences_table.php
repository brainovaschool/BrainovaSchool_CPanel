<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** An employee's own remembered custom time for one of the day's template
 *  slots (e.g. nudging the 09:00-10:00 row to 09:15-10:15). Keyed by the
 *  slot's original, un-customized start time rather than its position in
 *  the array, so it stays attached to the right row even once that row is
 *  already showing the customized time. Purely a per-slot display default
 *  for days that don't have an entry yet - never touches an entry already
 *  saved under its own start_time. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_work_log_slot_preferences')) {
            Schema::create('portal_work_log_slot_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('default_start'); // the template's own, un-customized "H:i"
                $table->time('start_time');
                $table->time('end_time');
                $table->timestamps();

                $table->unique(['staff_id', 'default_start']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_work_log_slot_preferences');
    }
};
