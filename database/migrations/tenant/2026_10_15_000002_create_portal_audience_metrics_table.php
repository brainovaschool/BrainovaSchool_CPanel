<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per platform per day; `values` holds the tracked metrics for
 *  that platform (e.g. {"Views": 1200, "Followers": 340}) per the spec's
 *  "AudienceMetric: date, platform, values (metric id to number)". */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_audience_metrics')) {
            Schema::create('portal_audience_metrics', function (Blueprint $table) {
                $table->id();
                $table->date('date');
                $table->string('platform');
                $table->json('values');
                $table->unsignedBigInteger('recorded_by'); // users.id
                $table->timestamps();

                $table->unique(['date', 'platform']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_audience_metrics');
    }
};
