<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Open-task "Visible to" used to be a single staff allow-list. This adds
 *  a by-designation allow-list (e.g. "all Coordinators") and a separate
 *  exclude-list (specific people who should NOT see it even if their
 *  designation or the staff allow-list would otherwise include them) -
 *  see PortalTask::isVisibleTo(). Both null = no change in behaviour for
 *  every existing task. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_tasks', 'visible_to_role_ids')) {
                $table->json('visible_to_role_ids')->nullable()->after('visible_to_staff_ids');
            }
            if (!Schema::hasColumn('portal_tasks', 'hidden_from_staff_ids')) {
                $table->json('hidden_from_staff_ids')->nullable()->after('visible_to_role_ids');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('portal_tasks', 'hidden_from_staff_ids')) {
                $table->dropColumn('hidden_from_staff_ids');
            }
            if (Schema::hasColumn('portal_tasks', 'visible_to_role_ids')) {
                $table->dropColumn('visible_to_role_ids');
            }
        });
    }
};
