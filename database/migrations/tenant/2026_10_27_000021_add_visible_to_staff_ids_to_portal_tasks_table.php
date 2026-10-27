<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Null/empty = open to every employee to see and claim, same as today.
 *  A non-empty list of staff ids restricts an Open paid task to just
 *  them - e.g. only the people who've shown they can handle that kind
 *  of reel. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_tasks', 'visible_to_staff_ids')) {
            Schema::table('portal_tasks', function (Blueprint $table) {
                $table->json('visible_to_staff_ids')->nullable()->after('paid');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_tasks', function (Blueprint $table) {
            $table->dropColumn('visible_to_staff_ids');
        });
    }
};
