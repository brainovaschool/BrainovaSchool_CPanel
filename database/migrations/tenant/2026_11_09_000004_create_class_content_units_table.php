<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The "big picture" layer above Module: Subject -> Unit -> Module -> Lesson.
 *  Scoped the same way a Module is (session/class/section/subject) since a
 *  Unit groups a teacher's own modules for one class+subject, not a
 *  cross-class concept. No review workflow of its own - it's an
 *  organisational label; visibility to students is entirely inherited from
 *  whether it holds any Approved module (see ClassContentUnitRepository). */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('class_content_units')) {
            Schema::create('class_content_units', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('sessions')->cascadeOnDelete();
                $table->foreignId('classes_id')->constrained('classes')->cascadeOnDelete();
                $table->foreignId('section_id')->nullable()->constrained('sections')->nullOnDelete();
                $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('class_content_units');
    }
};
