<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/** Daily Plan & Weekly Goals (phase 8) is being replaced outright by the
 *  Yearly Goals planner (portal_goals) - nothing else in the app reads
 *  either of these two tables, so they're dropped rather than migrated. */
return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('portal_weekly_goals');
        Schema::dropIfExists('portal_daily_plans');
    }

    public function down()
    {
        // Intentionally not reversible - the feature itself is gone.
    }
};
