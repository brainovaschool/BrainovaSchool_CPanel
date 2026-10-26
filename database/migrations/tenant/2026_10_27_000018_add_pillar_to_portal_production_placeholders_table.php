<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A Production Plan day can now be reserved for just a Pillar, before
 *  even a Series is picked - series_name stays NOT NULL (can't be freed
 *  of that without doctrine/dbal, same constraint as other migrations
 *  this session), so a pillar-only placeholder stores '' there instead
 *  of null. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_production_placeholders', 'pillar')) {
            Schema::table('portal_production_placeholders', function (Blueprint $table) {
                $table->string('pillar')->nullable()->after('date');
            });
        }
    }

    public function down()
    {
        Schema::table('portal_production_placeholders', function (Blueprint $table) {
            $table->dropColumn('pillar');
        });
    }
};
