<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Growth Pay — base + bonus per employee per month (handoff spec phase
 *  8). Per the admin's own description, this is "calculated in the
 *  portal, then I transfer from my account" — i.e. the admin enters and
 *  approves the numbers herself (there's no automated milestone engine
 *  to compute a bonus from), and marking it paid records a real Expense,
 *  exactly like phase 7's paid-task payouts. Never touches any fees_*
 *  table. One row per staff per month — the unique index makes it
 *  impossible to double-create, and status + expense_id together make
 *  double-paying impossible. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_growth_pay')) {
            Schema::create('portal_growth_pay', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->string('month', 7); // YYYY-MM
                $table->decimal('base_amount', 10, 2)->default(0);
                $table->decimal('bonus_amount', 10, 2)->default(0);
                $table->text('note')->nullable();

                $table->string('status', 10)->default('unpaid'); // unpaid | paid
                $table->unsignedBigInteger('marked_paid_by')->nullable();
                $table->timestamp('marked_paid_at')->nullable();
                $table->unsignedBigInteger('expense_id')->nullable();

                $table->timestamps();

                $table->unique(['staff_id', 'month']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_growth_pay');
    }
};
