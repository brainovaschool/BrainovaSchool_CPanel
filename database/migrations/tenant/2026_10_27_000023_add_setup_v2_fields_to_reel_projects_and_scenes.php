<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Reel Studio's "Reel Setup" rework: a per-scene length (used to compute
 *  how many scenes a reel needs from its target length), a dedicated cover
 *  image, and admin-defined custom fields (Theme, End Hook, Character,
 *  etc. - same definitions already used on the Social Board's own Reel
 *  form) for standalone projects that have no suggested Reel to read those
 *  from. Scenes drop their old per-character structure for one combined
 *  Dialogue box - existing per-character lines are folded into it so nothing
 *  already written is lost. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('reel_projects', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_projects', 'scene_length_seconds')) {
                $table->unsignedInteger('scene_length_seconds')->nullable()->after('target_duration_seconds');
            }
            if (!Schema::hasColumn('reel_projects', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('character_ids');
            }
            if (!Schema::hasColumn('reel_projects', 'cover_upload_id')) {
                $table->unsignedBigInteger('cover_upload_id')->nullable()->after('custom_fields');
            }
        });

        Schema::table('reel_scenes', function (Blueprint $table) {
            if (!Schema::hasColumn('reel_scenes', 'dialogue')) {
                $table->longText('dialogue')->nullable()->after('description');
            }
        });

        if (Schema::hasTable('reel_scene_characters')) {
            $scenes = DB::table('reel_scenes')->whereNull('dialogue')->get(['id']);
            foreach ($scenes as $scene) {
                $lines = DB::table('reel_scene_characters')
                    ->join('reel_characters', 'reel_characters.id', '=', 'reel_scene_characters.reel_character_id')
                    ->where('reel_scene_characters.reel_scene_id', $scene->id)
                    ->orderBy('reel_scene_characters.sort_order')
                    ->get(['reel_characters.name', 'reel_scene_characters.dialogue'])
                    ->filter(fn ($row) => trim((string) $row->dialogue) !== '')
                    ->map(fn ($row) => $row->name . ': ' . trim($row->dialogue))
                    ->all();

                if ($lines) {
                    DB::table('reel_scenes')->where('id', $scene->id)->update(['dialogue' => implode("\n", $lines)]);
                }
            }
        }
    }

    public function down()
    {
        Schema::table('reel_projects', function (Blueprint $table) {
            $table->dropColumn(['scene_length_seconds', 'custom_fields', 'cover_upload_id']);
        });
        Schema::table('reel_scenes', function (Blueprint $table) {
            $table->dropColumn('dialogue');
        });
    }
};
