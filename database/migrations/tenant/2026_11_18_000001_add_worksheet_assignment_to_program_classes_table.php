<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A lesson already has one Quiz slot and one Project slot (title,
 *  description, link) - this adds Worksheet and Assignment as two more
 *  slots of the exact same shape, plus a max-marks column for all four,
 *  so each gradable item has something for program_class_marks to be
 *  graded out of. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('program_classes', function (Blueprint $table) {
            if (!Schema::hasColumn('program_classes', 'quiz_max_marks')) {
                $table->unsignedInteger('quiz_max_marks')->nullable()->after('quiz_link_url');
            }
            if (!Schema::hasColumn('program_classes', 'project_max_marks')) {
                $table->unsignedInteger('project_max_marks')->nullable()->after('quiz_max_marks');
            }
            if (!Schema::hasColumn('program_classes', 'worksheet_title')) {
                $table->string('worksheet_title')->nullable()->after('project_max_marks');
                $table->text('worksheet_description')->nullable()->after('worksheet_title');
                $table->string('worksheet_link_url', 500)->nullable()->after('worksheet_description');
                $table->unsignedInteger('worksheet_max_marks')->nullable()->after('worksheet_link_url');
            }
            if (!Schema::hasColumn('program_classes', 'assignment_title')) {
                $table->string('assignment_title')->nullable()->after('worksheet_max_marks');
                $table->text('assignment_description')->nullable()->after('assignment_title');
                $table->string('assignment_link_url', 500)->nullable()->after('assignment_description');
                $table->unsignedInteger('assignment_max_marks')->nullable()->after('assignment_link_url');
            }
        });
    }

    public function down()
    {
        Schema::table('program_classes', function (Blueprint $table) {
            $cols = [
                'quiz_max_marks', 'project_max_marks',
                'worksheet_title', 'worksheet_description', 'worksheet_link_url', 'worksheet_max_marks',
                'assignment_title', 'assignment_description', 'assignment_link_url', 'assignment_max_marks',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('program_classes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
