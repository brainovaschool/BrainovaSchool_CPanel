<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** One-time catch-up for the backlog of reels sitting with no code even
 *  though their series already has one set - mirrors the new
 *  SocialRepository::assignMissingReelCodes() pass that now runs every
 *  time Series settings are saved, just applied once here to today's
 *  existing rows. Additive only: a reel that already has a code is left
 *  alone, and rejected reels are skipped (nothing to number for a dead
 *  idea) - suggested ones ARE included, since that's exactly the pile
 *  this was written to clear. */
return new class extends Migration
{
    public function up()
    {
        $seriesCodes = DB::table('portal_reel_series')
            ->whereNotNull('code')->where('code', '!=', '')
            ->pluck('code', 'name');

        foreach ($seriesCodes as $seriesName => $seriesCode) {
            $next = DB::table('portal_reels')
                ->where('series_name', $seriesName)
                ->whereNotNull('code')
                ->count() + 1;

            $missing = DB::table('portal_reels')
                ->where('series_name', $seriesName)
                ->whereNull('code')
                ->where('status', '!=', 'rejected')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id']);

            foreach ($missing as $row) {
                DB::table('portal_reels')->where('id', $row->id)->update(['code' => $seriesCode . $next]);
                $next++;
            }
        }
    }

    public function down()
    {
        // Not reversible - which rows were actually blank beforehand isn't kept anywhere.
    }
};
