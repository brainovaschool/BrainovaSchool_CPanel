<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Reverts 2026_10_24_000002 - the "raise leave for Teacher/Staff"
        // path was never reachable (Select Role only ever offered
        // Student), so these columns were never meaningfully populated.
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'replacement_day')) {
                $table->dropColumn('replacement_day');
            }
            if (Schema::hasColumn('leave_requests', 'is_half_day')) {
                $table->dropColumn('is_half_day');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->date('replacement_day')->nullable()->after('leave_days');
            $table->boolean('is_half_day')->default(false)->after('replacement_day');
        });
    }
};
