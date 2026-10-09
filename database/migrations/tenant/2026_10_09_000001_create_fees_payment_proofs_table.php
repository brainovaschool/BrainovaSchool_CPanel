<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A backup way to pay for parents reluctant to put a card online: they pay
 *  by JazzCash/EasyPaisa/bank transfer/cash on their own, then upload proof
 *  (a screenshot, usually) here. This is deliberately NOT written straight
 *  into fees_collects — a submission only becomes a real, counted payment
 *  once a staff member reviews the proof and approves it (see
 *  FeesPaymentProofRepository::approve(), which then creates the normal
 *  FeesCollect row the rest of the app already knows how to show). Until
 *  approved it stays invisible to every existing fee/report/income screen,
 *  so a fake or wrong screenshot can never silently mark a fee as paid. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('fees_payment_proofs')) {
            Schema::create('fees_payment_proofs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('fees_assign_children_id')->constrained('fees_assign_childrens')->cascadeOnDelete();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->unsignedBigInteger('submitted_by_user_id');

                $table->string('payment_method', 30); // jazzcash | easypaisa | bank_transfer | cash | other
                $table->decimal('amount_claimed', 10, 2);
                $table->string('transaction_reference')->nullable();
                $table->date('paid_date');
                $table->unsignedBigInteger('proof_upload_id');
                $table->text('note')->nullable();

                $table->string('status', 20)->default('pending'); // pending | approved | rejected
                $table->unsignedBigInteger('reviewed_by')->nullable(); // staff id
                $table->timestamp('reviewed_at')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->unsignedBigInteger('fees_collect_id')->nullable(); // set once approved

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('fees_payment_proofs');
    }
};
