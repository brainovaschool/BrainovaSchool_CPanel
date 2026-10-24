<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            // {"replacement_day": [5], "half_day_full_day": [5]} - lists of
            // SUBJECT role ids (whose leave record it is) that THIS role,
            // as viewer, gets to see that extra field for. Replaces the
            // short-lived decision_rules table (same idea, folded into the
            // role itself instead of a separate rules screen, since the
            // viewer side was always "whichever role you're editing").
            $table->json('leave_field_visibility')->nullable()->after('permissions');
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('leave_field_visibility');
        });
    }
};
