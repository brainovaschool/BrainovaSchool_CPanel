<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Each series gets its own colour, shown wherever its name appears
 *  (Reels list, Month Plan, a reel's own pages, ...) so the same series
 *  reads as the same colour everywhere. Existing series are seeded from
 *  a fixed palette (cycling if there are more series than colours) so
 *  the feature is useful immediately, not just once someone picks
 *  colours by hand; a series created later with no colour just falls
 *  back to the plain default tint already used today. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_reel_series', 'color')) {
            Schema::table('portal_reel_series', function (Blueprint $table) {
                $table->string('color', 7)->nullable()->after('code');
            });
        }

        $palette = ['#007585', '#6f42c1', '#d63384', '#fd7e14', '#198754', '#0d6efd', '#dc3545', '#20c997', '#6610f2', '#997404'];

        \Illuminate\Support\Facades\DB::table('portal_reel_series')
            ->whereNull('color')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($row, $i) use ($palette) {
                \Illuminate\Support\Facades\DB::table('portal_reel_series')
                    ->where('id', $row->id)
                    ->update(['color' => $palette[$i % count($palette)]]);
            });
    }

    public function down()
    {
        Schema::table('portal_reel_series', function (Blueprint $table) {
            $table->dropColumn('color');
        });
    }
};
