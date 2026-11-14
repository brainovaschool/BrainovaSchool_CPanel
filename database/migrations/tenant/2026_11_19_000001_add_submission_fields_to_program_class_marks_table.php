<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets a student submit their own work (a link or an uploaded file) for
 *  a lesson's quiz/project/worksheet/assignment, on the SAME row a
 *  teacher later grades - program_class_marks already is "this student's
 *  instance of this gradable item on this lesson", so a submission is
 *  just more columns on it rather than a whole new table. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('program_class_marks', function (Blueprint $table) {
            if (!Schema::hasColumn('program_class_marks', 'submission_link_url')) {
                $table->string('submission_link_url', 500)->nullable()->after('marks');
                $table->string('submission_storage_key')->nullable()->after('submission_link_url');
                $table->string('submission_original_filename')->nullable()->after('submission_storage_key');
                $table->timestamp('submitted_at')->nullable()->after('submission_original_filename');
            }
        });
    }

    public function down()
    {
        Schema::table('program_class_marks', function (Blueprint $table) {
            foreach (['submission_link_url', 'submission_storage_key', 'submission_original_filename', 'submitted_at'] as $column) {
                if (Schema::hasColumn('program_class_marks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
