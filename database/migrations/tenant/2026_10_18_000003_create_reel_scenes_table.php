<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reel_scenes')) {
            return;
        }

        Schema::create('reel_scenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_project_id')->constrained('reel_projects')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('duration_seconds')->default(8);
            $table->longText('description')->nullable();
            $table->longText('generated_prompt')->nullable()->comment('cached assembled prompt — not auto-invalidated by edits');
            $table->timestamp('generated_prompt_at')->nullable();
            $table->timestamps();

            $table->index(['reel_project_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reel_scenes');
    }
};
