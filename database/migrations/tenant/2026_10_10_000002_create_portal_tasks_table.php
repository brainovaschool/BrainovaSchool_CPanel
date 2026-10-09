<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Team Portal task board (handoff spec phase 2). assigned_to is nullable
 *  and paid/amount/pay_status exist now but are inert — they're for the
 *  later "Paid tasks" phase (7), which also adds the "open, unclaimed"
 *  status; this phase only ever creates tasks already assigned to
 *  someone, status starting at "assigned". */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_tasks')) {
            Schema::create('portal_tasks', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('priority', 10)->default('Medium'); // Low | Medium | High
                $table->boolean('urgent')->default(false);

                $table->foreignId('assigned_to')->nullable()->constrained('staff')->nullOnDelete();
                $table->unsignedBigInteger('assigned_by'); // users.id — who created/assigned it

                $table->date('assigned_date');
                $table->date('due_date');
                $table->decimal('est_hours', 6, 2)->nullable();

                $table->text('output')->nullable(); // what "done" looks like
                $table->string('format')->nullable();
                $table->text('refs')->nullable();
                $table->unsignedBigInteger('ref_upload_id')->nullable();
                $table->string('drive_link')->nullable();
                $table->text('notes')->nullable();

                $table->boolean('paid')->default(false);
                $table->decimal('amount', 10, 2)->nullable();
                $table->string('pay_status', 10)->nullable(); // unpaid | due | paid — phase 7

                $table->string('status', 20)->default('assigned'); // open | assigned | in_progress | submitted | under_review | revision | completed
                $table->unsignedTinyInteger('quality_score')->nullable();   // 0-5, admin-entered on approval
                $table->unsignedTinyInteger('revision_score')->nullable();  // 0-5, computed from revision count
                $table->unsignedTinyInteger('final_score')->nullable();     // 0-10

                $table->timestamp('completed_at')->nullable();
                $table->timestamp('claimed_at')->nullable(); // phase 7

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_tasks');
    }
};
