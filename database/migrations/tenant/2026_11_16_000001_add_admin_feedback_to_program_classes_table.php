<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Matches Class Content's real flow: Coordinator sends a submission on to
 *  Admin instead of approving it themselves, so there are two feedback
 *  slots, not one - sharing a single column let an Admin's note silently
 *  overwrite a Coordinator's note the first time Class Content built this,
 *  so this starts with them already separate. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('program_classes', 'admin_feedback')) {
            Schema::table('program_classes', function (Blueprint $table) {
                $table->text('admin_feedback')->nullable()->after('coordinator_feedback');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('program_classes', 'admin_feedback')) {
            Schema::table('program_classes', function (Blueprint $table) {
                $table->dropColumn('admin_feedback');
            });
        }
    }
};
