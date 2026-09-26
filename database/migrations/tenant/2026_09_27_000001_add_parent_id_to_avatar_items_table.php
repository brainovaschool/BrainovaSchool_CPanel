<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets one avatar_items row belong to another — specifically, a Building
 * belongs to a Hub. Reuses the same table as the wearable categories
 * (avatar/outfit/hat/accessory) rather than a parallel one, since Hubs and
 * Buildings need exactly the same tools those already have: an image
 * upload and the pos_x/pos_y/scale/rotation placement editor. They're
 * never worn, never appear in the student's Shop tab, and price_coins is
 * simply unused for them — see AvatarItem::CATEGORIES.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('avatar_items', 'parent_id')) {
            return;
        }

        Schema::table('avatar_items', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->after('category')->constrained('avatar_items')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
