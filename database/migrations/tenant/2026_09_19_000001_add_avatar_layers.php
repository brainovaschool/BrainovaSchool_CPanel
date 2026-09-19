<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Upgrades the avatar shop from "one whole look + one corner badge" to a
 * true layered dress-up system: a base character (avatar_item_id, unchanged)
 * plus an optional outfit layer, an optional hat layer, and any number of
 * simultaneously-worn accessories (glasses, backpack, held prop, ...) — each
 * stacked as its own transparent-PNG layer over the base body.
 *
 * accessory_item_id (the old single-accessory column) is left in place,
 * untouched, and its value is copied into the new multi-accessory table
 * below so nobody's current pick is lost — the column itself is simply no
 * longer read or written by the app after this migration.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('student_avatar_profiles', 'outfit_item_id')) {
            Schema::table('student_avatar_profiles', function (Blueprint $table) {
                $table->foreignId('outfit_item_id')->nullable()->after('avatar_item_id')->constrained('avatar_items')->nullOnDelete();
                $table->foreignId('hat_item_id')->nullable()->after('outfit_item_id')->constrained('avatar_items')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('student_avatar_equipped_accessories')) {
            Schema::create('student_avatar_equipped_accessories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
                $table->foreignId('avatar_item_id')->constrained('avatar_items')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['student_id', 'avatar_item_id']);
            });

            DB::table('student_avatar_profiles')
                ->whereNotNull('accessory_item_id')
                ->get(['student_id', 'accessory_item_id'])
                ->each(function ($row) {
                    DB::table('student_avatar_equipped_accessories')->insertOrIgnore([
                        'student_id'     => $row->student_id,
                        'avatar_item_id' => $row->accessory_item_id,
                        'created_at'     => now(),
                        'updated_at'     => now(),
                    ]);
                });
        }
    }

    public function down()
    {
        Schema::dropIfExists('student_avatar_equipped_accessories');

        Schema::table('student_avatar_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('hat_item_id');
            $table->dropConstrainedForeignId('outfit_item_id');
        });
    }
};
