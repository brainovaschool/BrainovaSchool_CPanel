<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** Replaces the separate "Output / deliverable" and "Prerequisites"
 *  fields with one free-text Description, shown right under the title
 *  instead of tucked inside "Details & tasks". Backfills from the two
 *  old columns (kept, just unused from here on) so nothing already
 *  typed into them is lost. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_goals', 'description')) {
            Schema::table('portal_goals', function (Blueprint $table) {
                $table->text('description')->nullable()->after('title');
            });

            DB::table('portal_goals')
                ->where(function ($q) {
                    $q->whereNotNull('output')->orWhereNotNull('prerequisites');
                })
                ->orderBy('id')
                ->get(['id', 'output', 'prerequisites'])
                ->each(function ($row) {
                    $parts = array_filter([
                        trim((string) $row->output) !== '' ? 'Output / deliverable: ' . $row->output : null,
                        trim((string) $row->prerequisites) !== '' ? 'Prerequisites: ' . $row->prerequisites : null,
                    ]);
                    if ($parts) {
                        DB::table('portal_goals')->where('id', $row->id)->update(['description' => implode("\n\n", $parts)]);
                    }
                });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('portal_goals', 'description')) {
            Schema::table('portal_goals', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
    }
};
