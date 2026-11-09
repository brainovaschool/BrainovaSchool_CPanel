<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A lesson has AT MOST one Project and one Quiz (matching the approved
 *  mockup - not a list like Activities), so these live as plain nullable
 *  columns on the lesson itself rather than their own child tables. A
 *  lesson "has" one once its *_title is filled in. Deliberately simple for
 *  phase 1: self-reported "mark as done" (see class_content_completions),
 *  no file upload or real grading - the quiz `score` column on that table
 *  is reserved for a later, real integration rather than a half-built one
 *  now. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('class_content_lessons', function (Blueprint $table) {
            $table->string('project_title')->nullable()->after('video_url');
            $table->text('project_description')->nullable()->after('project_title');
            $table->string('project_link_url', 500)->nullable()->after('project_description');

            $table->string('quiz_title')->nullable()->after('project_link_url');
            $table->text('quiz_description')->nullable()->after('quiz_title');
            $table->string('quiz_link_url', 500)->nullable()->after('quiz_description');
        });
    }

    public function down()
    {
        Schema::table('class_content_lessons', function (Blueprint $table) {
            $table->dropColumn(['project_title', 'project_description', 'project_link_url', 'quiz_title', 'quiz_description', 'quiz_link_url']);
        });
    }
};
