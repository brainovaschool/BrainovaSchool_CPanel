<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Production Plan lets a day be reserved for a Series before a specific
 *  Topic/reel is picked ("I know Speak Smarter is due the 5th, haven't
 *  decided which topic yet") - this holds those placeholder slots. A slot
 *  is replaced the moment a real reel's deadline is set for that same day
 *  and position; it's never itself promoted into a reel. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_production_placeholders')) {
            Schema::create('portal_production_placeholders', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('series_name');
                $table->timestamps();
                $table->index('date');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_production_placeholders');
    }
};
