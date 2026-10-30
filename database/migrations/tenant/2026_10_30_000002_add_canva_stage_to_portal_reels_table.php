<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Inserts a Canva checkpoint between the existing Prompt and Video
 *  (drive_link) stages - same shape as those two (link + approved_at +
 *  feedback), so submitStageLink()/approveStage() just grow a third
 *  branch instead of a new mechanism. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'canva_link')) {
                $table->string('canva_link')->nullable()->after('prompt_feedback');
            }
            if (!Schema::hasColumn('portal_reels', 'canva_approved_at')) {
                $table->timestamp('canva_approved_at')->nullable()->after('canva_link');
            }
            if (!Schema::hasColumn('portal_reels', 'canva_feedback')) {
                $table->text('canva_feedback')->nullable()->after('canva_approved_at');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            foreach (['canva_link', 'canva_approved_at', 'canva_feedback'] as $col) {
                if (Schema::hasColumn('portal_reels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
