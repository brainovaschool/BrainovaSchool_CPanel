<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The reviewer could already leave a text comment when sending work
 *  back for revision or approving it (admin_comment) - this lets them
 *  attach a file too (e.g. a marked-up copy, a corrected version). */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_task_submissions', 'admin_upload_id')) {
            Schema::table('portal_task_submissions', function (Blueprint $table) {
                $table->unsignedBigInteger('admin_upload_id')->nullable()->after('admin_comment');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_task_submissions', function (Blueprint $table) {
            $table->dropColumn('admin_upload_id');
        });
    }
};
