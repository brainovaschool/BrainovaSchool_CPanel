<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** The previous migration's backfill kept the old "Output / deliverable:"
 *  and "Prerequisites:" field names as literal text inside the new
 *  description, so nothing was lost — but now that the fields themselves
 *  are gone from the UI, those labels are just clutter. Strips them,
 *  keeping the actual text underneath. One-off, so no down(). */
return new class extends Migration
{
    public function up()
    {
        DB::table('portal_goals')
            ->whereNotNull('description')
            ->get(['id', 'description'])
            ->each(function ($row) {
                $clean = preg_replace('/^Output \/ deliverable:\s*/mi', '', (string) $row->description);
                $clean = preg_replace('/^Prerequisites:\s*/mi', '', $clean);
                $clean = trim($clean);
                if ($clean !== $row->description) {
                    DB::table('portal_goals')->where('id', $row->id)->update(['description' => $clean]);
                }
            });
    }

    public function down()
    {
        //
    }
};
