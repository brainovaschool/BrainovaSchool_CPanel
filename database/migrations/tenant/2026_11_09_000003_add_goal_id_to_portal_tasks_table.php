<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Links a task to the Yearly Goal (or sub-goal/step) it was assigned
 *  under from the Planning Board, so a goal can show its own tasks and
 *  roll up a real progress % from how many of them are completed. Deleting
 *  the goal later never deletes the task - it just goes back to being a
 *  normal, un-linked task (nullOnDelete), same protective pattern as the
 *  task <-> payout relationship. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            $table->foreignId('goal_id')->nullable()->after('id')->constrained('portal_goals')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('goal_id');
        });
    }
};
