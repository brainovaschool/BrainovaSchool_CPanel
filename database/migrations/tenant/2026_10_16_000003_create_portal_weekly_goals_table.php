<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_weekly_goals')) {
            Schema::create('portal_weekly_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('week_start'); // Monday
                $table->text('goal_text')->nullable();
                $table->string('target_metric')->nullable();
                $table->boolean('achieved')->default(false);
                $table->timestamps();

                $table->unique(['staff_id', 'week_start']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_weekly_goals');
    }
};
