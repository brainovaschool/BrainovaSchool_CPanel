<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Drives the Half Leave column on the Teacher Attendance grid.
            // (Not the same migration as the earlier reverted
            // replacement_day/is_half_day pair - that one also added
            // replacement_day, which isn't needed here.)
            if (!Schema::hasColumn('leave_requests', 'is_half_day')) {
                $table->boolean('is_half_day')->default(false)->after('leave_days');
            }
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            if (Schema::hasColumn('leave_requests', 'is_half_day')) {
                $table->dropColumn('is_half_day');
            }
        });
    }
};
