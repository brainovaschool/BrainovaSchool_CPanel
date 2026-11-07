<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A work log entry's notes used to be plain text only - no way to attach
 *  the actual document or reference link the work was about. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_work_log_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_work_log_entries', 'link')) {
                $table->string('link')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('portal_work_log_entries', 'upload_id')) {
                $table->foreignId('upload_id')->nullable()->after('link')->constrained('uploads')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('portal_work_log_entries', function (Blueprint $table) {
            if (Schema::hasColumn('portal_work_log_entries', 'upload_id')) {
                $table->dropConstrainedForeignId('upload_id');
            }
            if (Schema::hasColumn('portal_work_log_entries', 'link')) {
                $table->dropColumn('link');
            }
        });
    }
};
