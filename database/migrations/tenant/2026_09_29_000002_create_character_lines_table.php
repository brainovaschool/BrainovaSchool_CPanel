<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Brainbot and Kea's dialogue used to live in config/characters.php — only
 * a developer could edit it. This moves it into the database so it's a
 * Website Setup screen instead, matching every other piece of content in
 * this system (upload an image, fill in fields, save). Seeded once from
 * that config file so nothing already written is lost — see
 * CharacterLineSeeder.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('character_lines')) {
            return;
        }

        Schema::create('character_lines', function (Blueprint $table) {
            $table->id();
            $table->string('character', 30);
            $table->string('context', 60);
            $table->text('line');
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();

            $table->index(['character', 'context', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('character_lines');
    }
};
