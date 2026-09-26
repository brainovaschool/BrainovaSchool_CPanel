<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a student has dragged each "base" item they own to on their own
 * island — unlike Hub/Building (one shared layout for every student), a
 * base's position is personal. A row is created the moment the item is
 * bought (placed at the item's default spot inside its hub) and updated
 * whenever the student drags it or nudges it with the arrow keys.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_island_placements')) {
            return;
        }

        Schema::create('student_island_placements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('avatar_item_id')->constrained('avatar_items')->cascadeOnDelete();
            $table->decimal('pos_x', 6, 2)->default(50);
            $table->decimal('pos_y', 6, 2)->default(50);
            $table->timestamps();

            $table->unique(['student_id', 'avatar_item_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_island_placements');
    }
};
