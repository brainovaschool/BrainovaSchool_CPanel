<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Base" (category = base) used to mean a coin-bought item locked to one
 * hub's circle — the product plan calls this a "yard decoration" instead:
 * something a student buys and places anywhere on their own island, not
 * tied to any one subject zone. This clears parent_id off any base rows
 * created under the old rule so they're free-roam from here on; nothing
 * else about them (image, price, placement) changes.
 */
return new class extends Migration
{
    public function up()
    {
        DB::table('avatar_items')
            ->where('category', 'base')
            ->whereNotNull('parent_id')
            ->update(['parent_id' => null]);
    }

    public function down()
    {
        // Not reversible — which hub each item used to belong to isn't kept.
    }
};
