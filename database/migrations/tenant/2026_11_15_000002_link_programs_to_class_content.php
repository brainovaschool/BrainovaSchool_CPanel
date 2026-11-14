<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A Program (the public course catalogue - "Junior Coding Explorers",
 *  "Robotics") is a different record from the Classes/Section/Subject it's
 *  actually taught under in Class Content and Homework. These three
 *  optional columns are the link: once set, the Course Details block on a
 *  category's Overview page can pull that Program's real classes, materials
 *  and homework instead of showing the "not linked yet" empty state. Every
 *  Program without them keeps behaving exactly as it does today.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'classes_id')) {
                $table->foreignId('classes_id')->nullable()->after('program_focus_id')
                    ->constrained('classes')->nullOnDelete();
            }
            if (!Schema::hasColumn('programs', 'section_id')) {
                $table->foreignId('section_id')->nullable()->after('classes_id')
                    ->constrained('sections')->nullOnDelete();
            }
            if (!Schema::hasColumn('programs', 'subject_id')) {
                $table->foreignId('subject_id')->nullable()->after('section_id')
                    ->constrained('subjects')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            foreach (['classes_id', 'section_id', 'subject_id'] as $col) {
                if (Schema::hasColumn('programs', $col)) {
                    $table->dropConstrainedForeignId($col);
                }
            }
        });
    }
};
