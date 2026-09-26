<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which Program a Hub represents, so the island can dull hubs (and the base
 * items that belong to them) a student isn't enrolled in yet — nudging them
 * toward trying every subject rather than only the one they already like.
 * Only meaningful on category='hub' rows; left null means "no gating", so
 * a hub with no program picked is always open to everyone.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('avatar_items', 'program_id')) {
            return;
        }

        Schema::table('avatar_items', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('parent_id')->constrained('programs')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('avatar_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
        });
    }
};
