<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // The substitute/make-up day for a STAFF leave only - never
            // shown or set for student leave. Re-added deliberately: an
            // earlier pass added this same column before staff leave could
            // even be created (so it was always empty) and was reverted;
            // staff leave creation exists now, so this has real data to hold.
            if (!Schema::hasColumn('leave_requests', 'replacement_day')) {
                $table->date('replacement_day')->nullable()->after('is_half_day');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'replacement_day')) {
                $table->dropColumn('replacement_day');
            }
        });
    }
};
