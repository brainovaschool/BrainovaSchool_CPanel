<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The Class list's "Sr No" column was only ever the row's position on the
 *  page (newest-first) - there was nothing to actually reorder, so the
 *  Edit form had no serial-number field to show. This gives Classes a
 *  real, editable order to list by instead. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('classes', 'sort_order')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('classes', 'sort_order')) {
            Schema::table('classes', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
