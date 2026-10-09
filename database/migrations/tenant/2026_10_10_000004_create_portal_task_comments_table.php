<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_task_comments')) {
            Schema::create('portal_task_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('task_id')->constrained('portal_tasks')->cascadeOnDelete();
                $table->unsignedBigInteger('user_id'); // users.id — admin or the employee
                $table->text('body');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_task_comments');
    }
};
