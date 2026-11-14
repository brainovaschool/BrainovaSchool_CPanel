<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Duration ("6 weeks") is a plain description, not an actual date range -
 *  these are the real start/end dates for Course Details to show
 *  alongside it, both optional like every other descriptive field here. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'start_date')) {
                $table->date('start_date')->nullable()->after('duration');
            }
            if (!Schema::hasColumn('programs', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            foreach (['start_date', 'end_date'] as $col) {
                if (Schema::hasColumn('programs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
