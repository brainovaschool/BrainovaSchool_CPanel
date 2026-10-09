<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Handoff spec phase 7 — the approved design: staff payouts are
 *  EXPENSES, never student fee entries, and this table never touches any
 *  fees_* table. One row per paid task, ever — the unique index on
 *  task_id is what makes "mark paid" idempotent at the database level,
 *  not just in application code. expense_id points at the real Expense
 *  row created at the same time (see TaskRepository::markPaid()), so the
 *  payout shows up in the existing Expense report automatically. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_payouts')) {
            Schema::create('portal_payouts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->unique()->constrained('portal_tasks')->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->unsignedBigInteger('marked_paid_by'); // users.id
                $table->timestamp('marked_paid_at');
                $table->unsignedBigInteger('expense_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_payouts');
    }
};
