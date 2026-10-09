<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Handoff spec phase 4. One row per filled slot (or extra entry) in an
 *  employee's day. Entries can't overlap and can't be added/changed once
 *  the day is submitted (see portal_day_submissions) — enforced in
 *  WorkLogRepository, not the schema. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_work_log_entries')) {
            Schema::create('portal_work_log_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('date');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('activity')->nullable(); // standard activity name, set when task_id is null
                $table->foreignId('task_id')->nullable()->constrained('portal_tasks')->nullOnDelete();
                $table->text('notes')->nullable();
                $table->boolean('is_extra')->default(false); // true when it doesn't match a template slot
                $table->timestamps();

                $table->index(['staff_id', 'date']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_work_log_entries');
    }
};
