<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Marks a staff member's work log for one date as locked — "After the
 *  employee submits the day, entries are locked" (business rule 7). One
 *  row per staff per day, enforced by the unique index. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_day_submissions')) {
            Schema::create('portal_day_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('date');
                $table->timestamp('submitted_at');
                $table->timestamps();

                $table->unique(['staff_id', 'date']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_day_submissions');
    }
};
