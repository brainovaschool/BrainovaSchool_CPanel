<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Coordinator's and Admin's feedback were sharing one column
 *  (coordinator_feedback), so an Admin's note silently overwrote the
 *  Coordinator's and both displayed under the same "Coordinator feedback"
 *  label regardless of who actually wrote it. Splitting them so a Teacher
 *  (or Coordinator, when Admin sends a module back to them) can see both,
 *  correctly attributed. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('class_content_modules', 'admin_feedback')) {
            Schema::table('class_content_modules', function (Blueprint $table) {
                $table->text('admin_feedback')->nullable()->after('coordinator_feedback');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('class_content_modules', 'admin_feedback')) {
            Schema::table('class_content_modules', function (Blueprint $table) {
                $table->dropColumn('admin_feedback');
            });
        }
    }
};
