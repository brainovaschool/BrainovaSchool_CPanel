<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Curious Minds' own series code changed from BC to AC at some point -
 *  a reel only ever gets its code once, when it first leaves "suggested"
 *  (see PortalReel::booted()), so every reel suggested before that
 *  change is still carrying its old BC{n} code. One-time data fix:
 *  rename each to AC{n}, same number, Curious Minds reels only. */
return new class extends Migration
{
    public function up()
    {
        DB::table('portal_reels')
            ->where('series_name', 'Curious Minds')
            ->where('code', 'like', 'BC%')
            ->get(['id', 'code'])
            ->each(function ($row) {
                DB::table('portal_reels')->where('id', $row->id)->update([
                    'code' => 'AC' . substr($row->code, 2),
                ]);
            });
    }

    public function down()
    {
        // Not safely reversible - reels genuinely suggested after the
        // series code became AC would also get dragged back to BC.
    }
};
