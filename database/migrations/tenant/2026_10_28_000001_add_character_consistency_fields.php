<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets each scene say which 1-3 characters actually appear in it (so AI
 *  dialogue and the compiled document only ever reference those, not the
 *  whole project roster), and lets each Reel Character carry an optional
 *  uploaded reference document plus any number of costume/outfit images -
 *  on top of the existing appearance/voice/rules text fields, not instead
 *  of them. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('reel_scenes', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_scenes', 'character_ids')) {
                $table->json('character_ids')->nullable()->after('dialogue')->comment('up to 3 reel_characters.id - who is in this scene');
            }
        });

        Schema::table('reel_characters', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_characters', 'description_upload_id')) {
                $table->unsignedBigInteger('description_upload_id')->nullable()->after('rules_text')->comment('optional uploaded reference doc, supplements the text fields - does not replace them');
            }
        });

        if (!Schema::hasTable('reel_character_costumes')) {
            Schema::create('reel_character_costumes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reel_character_id')->constrained('reel_characters')->cascadeOnDelete();
                $table->unsignedBigInteger('upload_id')->comment('uploads.id');
                $table->string('label')->nullable()->comment('e.g. "Winter outfit", "Lab coat"');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('reel_character_costumes');
        Schema::table('reel_characters', function (Blueprint $table) {
            $table->dropColumn('description_upload_id');
        });
        Schema::table('reel_scenes', function (Blueprint $table) {
            $table->dropColumn('character_ids');
        });
    }
};
