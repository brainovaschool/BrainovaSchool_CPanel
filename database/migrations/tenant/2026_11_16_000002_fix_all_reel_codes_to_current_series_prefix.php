<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** Generalises the previous Curious Minds BC->AC fix to every series - a
 *  reel only gets its code once (when it first leaves "suggested", see
 *  PortalReel::booted()), so any series whose own code setting has
 *  changed since left its older reels stuck on the old prefix. For
 *  each series, any of its reels whose code doesn't already start with
 *  that series' CURRENT code gets the same trailing number re-stamped
 *  with the current prefix - e.g. BC6 -> AC6. The running number is
 *  counted per series_name regardless of prefix (see booted()), so
 *  numbers never collide across a prefix change. Series with no code
 *  set are left untouched. */
return new class extends Migration
{
    public function up()
    {
        $series = DB::table('portal_reel_series')
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->get(['name', 'code']);

        foreach ($series as $s) {
            DB::table('portal_reels')
                ->where('series_name', $s->name)
                ->whereNotNull('code')
                ->where('code', 'not like', $s->code . '%')
                ->get(['id', 'code'])
                ->each(function ($row) use ($s) {
                    $number = preg_replace('/\D/', '', $row->code);
                    if ($number === '') {
                        return;
                    }
                    DB::table('portal_reels')->where('id', $row->id)->update([
                        'code' => $s->code . $number,
                    ]);
                });
        }
    }

    public function down()
    {
        // Not safely reversible - there's no record of which prefix each
        // reel's code originally had before this ran.
    }
};
