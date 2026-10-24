<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // The day a staff member will make up the missed work -
            // meaningless for a student's leave, which is why visibility is
            // gated per subject role on the Decisions settings page.
            $table->date('replacement_day')->nullable()->after('leave_days');
            $table->boolean('is_half_day')->default(false)->after('replacement_day');
        });
    }

    public function down()
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropColumn(['replacement_day', 'is_half_day']);
        });
    }
};
