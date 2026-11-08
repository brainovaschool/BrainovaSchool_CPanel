<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Yearly Goals planner - a self-referencing tree (parent_id -> itself) so
 *  a Goal can hold Sub-goals, which can hold their own Sub-steps, to any
 *  depth, with no separate table per level. Deleting a goal cascades to
 *  its whole sub-tree (it's genuinely gone), but never touches a task
 *  that was assigned under it - see goal_id on portal_tasks below. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_goals')) {
            Schema::create('portal_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('parent_id')->nullable()->constrained('portal_goals')->cascadeOnDelete();
                $table->string('title');
                $table->text('output')->nullable();       // what "done" looks like
                $table->text('prerequisites')->nullable();
                $table->foreignId('owner_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->string('status', 20)->default('not_started'); // not_started | in_progress | done
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->unsignedBigInteger('created_by')->nullable(); // users.id
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_goals');
    }
};
