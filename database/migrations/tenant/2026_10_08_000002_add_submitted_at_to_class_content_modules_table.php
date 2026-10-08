<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Nothing recorded WHEN a module was actually submitted — Coordinator and
 *  Admin had no way to tell how long something had been sitting in their
 *  queue. Set once on first submit, cleared back to null only if the
 *  teacher is sent all the way back to Draft (never happens today, kept
 *  for correctness) so it always reflects the current review cycle. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('class_content_modules', 'submitted_at')) {
            Schema::table('class_content_modules', function (Blueprint $table) {
                $table->timestamp('submitted_at')->nullable()->after('review_status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('class_content_modules', 'submitted_at')) {
            Schema::table('class_content_modules', function (Blueprint $table) {
                $table->dropColumn('submitted_at');
            });
        }
    }
};
