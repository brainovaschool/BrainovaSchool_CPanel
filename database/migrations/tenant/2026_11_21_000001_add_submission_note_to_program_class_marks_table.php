<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A student's own free-text note to the teacher, alongside whatever
 *  link/file they submit (submission_link_url/submission_storage_key) -
 *  same additive pattern those columns were added with. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('program_class_marks', 'submission_note')) {
            Schema::table('program_class_marks', function (Blueprint $table) {
                $table->text('submission_note')->nullable()->after('submission_original_filename');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('program_class_marks', 'submission_note')) {
            Schema::table('program_class_marks', function (Blueprint $table) {
                $table->dropColumn('submission_note');
            });
        }
    }
};
