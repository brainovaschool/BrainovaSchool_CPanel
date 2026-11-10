<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets the admin pick, per reel, which of a character's costumes to
 *  use for this particular reel - the same character is reused across
 *  many reels, often wearing a different outfit each time. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'character_costumes')) {
                $table->json('character_costumes')->nullable()->after('custom_fields')
                    ->comment('{character name: reel_character_costumes.id} - which costume to use for this reel');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            $table->dropColumn('character_costumes');
        });
    }
};
