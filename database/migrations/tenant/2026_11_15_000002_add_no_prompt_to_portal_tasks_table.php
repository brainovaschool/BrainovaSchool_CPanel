<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A second, independent flag alongside prompt_ready (see the previous
 *  migration) - some reels were never going to have a written prompt at
 *  all, so ticking "No prompt" instead of "Prompt ready" both unblocks
 *  claiming (same effect as prompt_ready) and shows a different label
 *  ("No prompt" instead of "Ready") on the Paid Tasks page, so staff
 *  know not to expect one. See SocialRepository::bulkMarkReelsPaid()/
 *  setReelPromptReady(), which always set prompt_ready alongside this. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_tasks', 'no_prompt')) {
            Schema::table('portal_tasks', function (Blueprint $table) {
                $table->boolean('no_prompt')->default(false)->after('prompt_ready');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            $table->dropColumn('no_prompt');
        });
    }
};
