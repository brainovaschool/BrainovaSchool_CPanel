<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The Homework tab only ever knew Class + Section + Subject - nothing
 *  finer - so there was no way to show "this quiz belongs to this specific
 *  Class Content lesson" on the Course Details page. This is the one column
 *  that fixes that: optional, so every existing homework row (and the
 *  create/edit flow itself) keeps working exactly as it does today for
 *  anyone who doesn't pick a lesson.
 */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('homework', 'class_content_lesson_id')) {
            Schema::table('homework', function (Blueprint $table) {
                $table->foreignId('class_content_lesson_id')->nullable()->after('subject_id')
                    ->constrained('class_content_lessons')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        Schema::table('homework', function (Blueprint $table) {
            if (Schema::hasColumn('homework', 'class_content_lesson_id')) {
                $table->dropConstrainedForeignId('class_content_lesson_id');
            }
        });
    }
};
