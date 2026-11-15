<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** One-time cleanup for tasks left behind by deleting a reel before
 *  SocialRepository::destroyReel() started removing the linked task too
 *  - a task whose reel_id no longer points at any real reel, still Open
 *  and still showing up for staff to claim, with no reel left to do.
 *  Same payout guard as everywhere else: a task that's already been
 *  paid is left alone even if orphaned, so that audit trail never gets
 *  silently deleted. */
return new class extends Migration
{
    public function up()
    {
        DB::table('portal_tasks')
            ->whereNotNull('reel_id')
            ->whereNotIn('reel_id', DB::table('portal_reels')->select('id'))
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('portal_payouts')
                    ->whereColumn('portal_payouts.task_id', 'portal_tasks.id');
            })
            ->delete();
    }

    public function down()
    {
        // Not reversible - the deleted rows' data isn't kept anywhere.
    }
};
