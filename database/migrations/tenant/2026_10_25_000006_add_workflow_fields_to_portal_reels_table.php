<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Adds the suggest -> assign -> prompt -> video -> schedule workflow on
 *  top of the existing status pipeline:
 *  - assigned_to / is_open_paid: who's doing it (a specific content-team
 *    member, or left open as a paid task for anyone to pick up).
 *  - deadline: when the WORK is due, separate from planned_date (when it
 *    goes live - relabelled "Uploaded date" in the UI, column unchanged).
 *  - prompt_approved_at / video_approved_at: the two approval checkpoints
 *    the admin clears independently (drive_link doubles as the video
 *    link - relabelled in the UI, no new column needed for it). */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'assigned_to')) {
                $table->unsignedBigInteger('assigned_to')->nullable()->after('series_name');
            }
            if (!Schema::hasColumn('portal_reels', 'is_open_paid')) {
                $table->boolean('is_open_paid')->default(false)->after('assigned_to');
            }
            if (!Schema::hasColumn('portal_reels', 'deadline')) {
                $table->date('deadline')->nullable()->after('planned_date');
            }
            if (!Schema::hasColumn('portal_reels', 'prompt_approved_at')) {
                $table->timestamp('prompt_approved_at')->nullable()->after('prompt_link');
            }
            if (!Schema::hasColumn('portal_reels', 'video_approved_at')) {
                $table->timestamp('video_approved_at')->nullable()->after('drive_link');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            foreach (['assigned_to', 'is_open_paid', 'deadline', 'prompt_approved_at', 'video_approved_at'] as $col) {
                if (Schema::hasColumn('portal_reels', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
