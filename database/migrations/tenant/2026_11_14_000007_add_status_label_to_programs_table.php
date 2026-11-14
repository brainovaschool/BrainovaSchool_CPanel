<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** A separate field from the existing "badge" (which already holds
 *  subject/provider labels like "Maths" or "CODENOVA" for real programs
 *  - repurposing it would have overwritten that). This one is the
 *  course's own lifecycle stage - Ongoing / Upcoming / Coming Soon /
 *  Enrolling - shown as its own coloured pill, same idea as the
 *  existing badge pill but a different piece of information. Backfills
 *  real current data: the coding programme is ongoing, maths/english/
 *  urdu are upcoming, everything else still active is coming soon. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('programs', 'status_label')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->string('status_label', 20)->nullable()->after('badge');
            });
        }

        DB::table('programs')->where('status', 1)->update(['status_label' => 'coming_soon']);

        DB::table('programs')->where('status', 1)
            ->where('title', 'like', '%coding%')
            ->update(['status_label' => 'ongoing']);

        DB::table('programs')->where('status', 1)
            ->where(function ($q) {
                $q->where('title', 'like', '%math%')
                    ->orWhere('title', 'like', '%english%')
                    ->orWhere('title', 'like', '%urdu%');
            })
            ->update(['status_label' => 'upcoming']);
    }

    public function down()
    {
        if (Schema::hasColumn('programs', 'status_label')) {
            Schema::table('programs', function (Blueprint $table) {
                $table->dropColumn('status_label');
            });
        }
    }
};
