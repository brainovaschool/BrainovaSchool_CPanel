<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reel_scene_characters')) {
            return;
        }

        Schema::create('reel_scene_characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reel_scene_id')->constrained('reel_scenes')->cascadeOnDelete();
            $table->foreignId('reel_character_id')->constrained('reel_characters')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0)->comment('attribution order within the scene, 1-3');
            $table->longText('action_points')->nullable();
            $table->longText('dialogue')->nullable();
            $table->timestamps();

            $table->unique(['reel_scene_id', 'reel_character_id']);
            $table->index(['reel_scene_id', 'sort_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reel_scene_characters');
    }
};
