<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A fix is now categorised by which part of the site it's about
 *  (one of the sidebar's main categories), not assigned to a specific
 *  staff owner - owner_staff_id is left in place (existing data, if
 *  any, stays put) but the UI no longer reads or writes it. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_page_fixes', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_page_fixes', 'page_category')) {
                $table->string('page_category')->nullable()->after('description');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_page_fixes', function (Blueprint $table) {
            if (Schema::hasColumn('portal_page_fixes', 'page_category')) {
                $table->dropColumn('page_category');
            }
        });
    }
};
