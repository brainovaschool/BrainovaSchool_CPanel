<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Default true so every already-open paid task stays claimable exactly
 *  as before this column existed - it only ever turns false when a Mark
 *  As Paid form explicitly leaves "Prompt ready" unticked, for a reel
 *  whose prompt/shot list isn't written yet. A task sitting at false
 *  stays visible in the Paid Tasks list (so staff know it's coming) but
 *  can't be claimed - see PortalTask::prompt_ready and
 *  TaskRepository::claim(). */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_tasks', 'prompt_ready')) {
            Schema::table('portal_tasks', function (Blueprint $table) {
                $table->boolean('prompt_ready')->default(true)->after('paid');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            $table->dropColumn('prompt_ready');
        });
    }
};
