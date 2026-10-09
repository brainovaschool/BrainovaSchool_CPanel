<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Append-only submission/revision history for a portal task — per the
 *  handoff spec's business rule 4: "never delete or overwrite earlier
 *  submissions." Every row stays exactly as it was reviewed, forever. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_task_submissions')) {
            Schema::create('portal_task_submissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('portal_tasks')->cascadeOnDelete();
                $table->unsignedInteger('n'); // 1, 2, 3... per task
                $table->unsignedBigInteger('submitted_by'); // users.id

                $table->string('link')->nullable();
                $table->unsignedBigInteger('upload_id')->nullable();
                $table->text('comment')->nullable(); // employee's note

                $table->text('admin_comment')->nullable();
                $table->string('result', 10)->nullable(); // null | revision | approved
                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_task_submissions');
    }
};
