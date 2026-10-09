<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row of Team Portal settings — the editable revision-score table and
 *  task category list, per the handoff spec's "Settings" entity. Work-log
 *  fields (workdayHours, dayStart, lunchAfter, activities) belong to the
 *  later Work Log phase, not this one, so they're left out until that
 *  phase actually needs them. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_settings')) {
            Schema::create('portal_settings', function (Blueprint $table) {
                $table->id();
                $table->json('revision_scores'); // index = revision count, value = score out of 5; last index covers "that many or more"
                $table->json('categories');
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_settings');
    }
};
