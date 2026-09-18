<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-managed catalogue for the Kea avatar shop (Website Setup > Avatar
 * Gallery). Two categories:
 *   - 'avatar'    a full avatar look (image) the student can select as Kea.
 *   - 'accessory' a small badge/sticker shown on the corner of the chosen
 *                 avatar — not compositing, just a positioned overlay icon.
 * price_coins = 0 means it's free/starter and owned by everyone automatically.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('avatar_items')) {
            return;
        }

        Schema::create('avatar_items', function (Blueprint $table) {
            $table->id();
            $table->string('category', 20); // avatar | accessory
            $table->string('name');
            $table->string('image')->nullable();
            $table->unsignedInteger('price_coins')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('avatar_items');
    }
};
