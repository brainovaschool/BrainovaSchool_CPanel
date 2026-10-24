<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            // Separate from the workflow `status` column (open/assigned/
            // in_progress/etc.) - this is "archived or not", for bulk
            // deactivate on the Tasks list. Archived tasks drop out of the
            // default list without losing their history.
            if (!Schema::hasColumn('portal_tasks', 'active_status')) {
                $table->boolean('active_status')->default(true)->after('status');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('portal_tasks', 'active_status')) {
                $table->dropColumn('active_status');
            }
        });
    }
};
