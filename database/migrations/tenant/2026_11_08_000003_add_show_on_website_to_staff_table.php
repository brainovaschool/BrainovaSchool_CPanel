<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            // Defaults true so every already-active teacher keeps showing
            // on the public "Meet our teachers" section exactly as before -
            // admins then uncheck the handful (test accounts, etc.) that
            // shouldn't be public, instead of every teacher vanishing from
            // the site until someone opts each one back in.
            $table->boolean('show_on_website')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn('show_on_website');
        });
    }
};
