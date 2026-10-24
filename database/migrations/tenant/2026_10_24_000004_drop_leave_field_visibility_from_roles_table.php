<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Reverts 2026_10_24_000003 - the per-role Leave visibility idea
        // was replaced before any real data was ever entered into it.
        Schema::table('roles', function (Blueprint $table) {
            if (Schema::hasColumn('roles', 'leave_field_visibility')) {
                $table->dropColumn('leave_field_visibility');
            }
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->json('leave_field_visibility')->nullable()->after('permissions');
        });
    }
};
