<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Daily content plan — one free-text entry per content-team member per
 *  day (handoff spec phase 8). Who counts as "content team" is now
 *  whoever holds the Content responsibility (phase 5), not a hardcoded
 *  list of names — per the admin's own clarification. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_daily_plans')) {
            Schema::create('portal_daily_plans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('date');
                $table->text('plan_text')->nullable();
                $table->timestamps();

                $table->unique(['staff_id', 'date']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_daily_plans');
    }
};
