<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Four fields the Course Details preview flagged as missing from Program:
 *  a 1/2/3 skill level (Beginner/Intermediate/Advance), how many classes
 *  run per week, and the plain-text days/time the class meets. All
 *  optional, same as every other descriptive field on this form. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'skill_level')) {
                $table->unsignedTinyInteger('skill_level')->nullable()->after('grade');
            }
            if (!Schema::hasColumn('programs', 'classes_per_week')) {
                $table->unsignedTinyInteger('classes_per_week')->nullable()->after('lessons');
            }
            if (!Schema::hasColumn('programs', 'class_days')) {
                $table->string('class_days', 120)->nullable()->after('classes_per_week');
            }
            if (!Schema::hasColumn('programs', 'class_time')) {
                $table->string('class_time', 60)->nullable()->after('class_days');
            }
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            foreach (['skill_level', 'classes_per_week', 'class_days', 'class_time'] as $col) {
                if (Schema::hasColumn('programs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
