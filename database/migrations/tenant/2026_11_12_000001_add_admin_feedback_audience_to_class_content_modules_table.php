<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** admin_feedback was shown to the Teacher unconditionally, even when the
 *  Admin's decision was "back to coordinator" - a note meant only for the
 *  Coordinator to act on, not yet finalised for the Teacher. This flag lets
 *  the feedback panel tell the two cases apart: true means "coordinator's
 *  eyes only, teacher doesn't see it yet", set only on that decision and
 *  cleared again once Admin decides straight to approve/request_changes. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('class_content_modules', 'admin_feedback_to_coordinator')) {
            Schema::table('class_content_modules', function (Blueprint $table) {
                $table->boolean('admin_feedback_to_coordinator')->default(false)->after('admin_feedback');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('class_content_modules', 'admin_feedback_to_coordinator')) {
            Schema::table('class_content_modules', function (Blueprint $table) {
                $table->dropColumn('admin_feedback_to_coordinator');
            });
        }
    }
};
