<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets a student be enrolled in more than one class at once (short
 *  courses alongside their main grade class). A DEFAULT 'primary' on this
 *  ALTER backfills every existing row automatically — MySQL sets a new
 *  column's default value on all existing rows at alter-time — so every
 *  student who exists today keeps exactly the same single "primary"
 *  enrollment they already had, with zero behaviour change. "secondary"
 *  rows are the new, additive short-course enrollments; there is
 *  deliberately no DB constraint enforcing "only one primary per
 *  student" (MySQL has no partial/filtered unique index) — that's
 *  enforced in application code instead (see StudentRepository).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('session_class_students', 'enrollment_type')) {
            return;
        }

        Schema::table('session_class_students', function (Blueprint $table) {
            $table->string('enrollment_type', 20)->default('primary')->after('student_id');
        });

        // Belt-and-braces in case any row predates the column default
        // (e.g. restored from a backup) — make absolutely sure nothing
        // is left NULL, since every lookup below filters on this value.
        \Illuminate\Support\Facades\DB::table('session_class_students')
            ->whereNull('enrollment_type')
            ->update(['enrollment_type' => 'primary']);
    }

    public function down()
    {
        Schema::table('session_class_students', function (Blueprint $table) {
            $table->dropColumn('enrollment_type');
        });
    }
};
