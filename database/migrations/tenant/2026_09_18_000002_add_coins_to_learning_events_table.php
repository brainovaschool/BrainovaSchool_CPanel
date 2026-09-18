<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second, SPENDABLE currency alongside XP. XP is permanent and drives Brain
 * Level — it must never decrease. Coins are earned on the same events (see
 * LearningEventRepository::record()) and spent in the avatar shop, so
 * spending coins can never set back a student's Brain Level or mastery.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('learning_events', 'coins')) {
            return;
        }

        Schema::table('learning_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('coins')->default(0)->after('xp');
        });
    }

    public function down()
    {
        Schema::table('learning_events', function (Blueprint $table) {
            $table->dropColumn('coins');
        });
    }
};
