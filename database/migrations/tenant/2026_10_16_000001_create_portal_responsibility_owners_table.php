<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** A responsibility can now have more than one owner at once — "I can
 *  assign the content responsibility to anyone I want, it can be 1
 *  person, two or more" (admin's own words). Additive: the old single
 *  owner_staff_id column on portal_responsibilities is left in place
 *  (untouched schema-wise) but any existing single owner is copied into
 *  this new table so nothing already assigned goes quietly unowned —
 *  from here on this table is the only thing the app actually reads. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_responsibility_owners')) {
            Schema::create('portal_responsibility_owners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('responsibility_id')->constrained('portal_responsibilities')->cascadeOnDelete();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['responsibility_id', 'staff_id']);
            });

            if (Schema::hasColumn('portal_responsibilities', 'owner_staff_id')) {
                DB::table('portal_responsibilities')->whereNotNull('owner_staff_id')->get(['id', 'owner_staff_id'])->each(function ($row) {
                    DB::table('portal_responsibility_owners')->insertOrIgnore([
                        'responsibility_id' => $row->id,
                        'staff_id'          => $row->owner_staff_id,
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ]);
                });
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_responsibility_owners');
    }
};
