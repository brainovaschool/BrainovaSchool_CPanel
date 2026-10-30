<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets a reviewer attach a file (a marked-up screenshot, a note doc,
 *  etc.) alongside their text feedback at each of the three approval
 *  stages - same "Y/N approve + text + optional file" shape at all three. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            foreach (['prompt', 'canva', 'video'] as $stage) {
                $col = "{$stage}_feedback_upload_id";
                if (!Schema::hasColumn('portal_reels', $col)) {
                    $table->unsignedBigInteger($col)->nullable()->after("{$stage}_feedback");
                }
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            foreach (['prompt_feedback_upload_id', 'canva_feedback_upload_id', 'video_feedback_upload_id'] as $col) {
                if (Schema::hasColumn('portal_reels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
