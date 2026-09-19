<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where each layer sits on the avatar. Accessory/hat/outfit artwork is
 * normally drawn standalone (a headband centred on its own canvas), so
 * stacking it raw covers the whole character. These four numbers place it
 * instead: pos_x/pos_y are the anchor point as a percentage of the avatar
 * stage, scale is the item's width as a percentage of that stage, and
 * rotation tilts it. Set once per item in Website Setup (with a live
 * preview), then every student's avatar wears it correctly.
 *
 * Defaults (50/50/100/0) reproduce the previous full-bleed behaviour, so
 * base characters need no adjustment at all.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('avatar_items', 'pos_x')) {
            return;
        }

        Schema::table('avatar_items', function (Blueprint $table) {
            $table->decimal('pos_x', 6, 2)->default(50)->after('image');
            $table->decimal('pos_y', 6, 2)->default(50)->after('pos_x');
            $table->decimal('scale', 6, 2)->default(100)->after('pos_y');
            $table->smallInteger('rotation')->default(0)->after('scale');
        });
    }

    public function down()
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropColumn(['pos_x', 'pos_y', 'scale', 'rotation']);
        });
    }
};
