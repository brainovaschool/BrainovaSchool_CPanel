<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Recurring monthly targets per content category — the "month plan with
 *  category targets" screen compares these against how many of this
 *  month's reels/carousels (accepted or further along, business rule 10)
 *  actually landed in each category. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_category_targets')) {
            Schema::create('portal_category_targets', function (Blueprint $table) {
                $table->id();
                $table->string('category')->unique();
                $table->unsignedInteger('reel_target')->default(0);
                $table->unsignedInteger('carousel_target')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_category_targets');
    }
};
