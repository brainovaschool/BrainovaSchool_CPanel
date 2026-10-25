<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Optional feedback text alongside each prompt/video approval, shown on
 *  the Content Pool table (Prompt feedback / Video feedback columns). */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'prompt_feedback')) {
                $table->text('prompt_feedback')->nullable()->after('prompt_approved_at');
            }
            if (!Schema::hasColumn('portal_reels', 'video_feedback')) {
                $table->text('video_feedback')->nullable()->after('video_approved_at');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            foreach (['prompt_feedback', 'video_feedback'] as $col) {
                if (Schema::hasColumn('portal_reels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
