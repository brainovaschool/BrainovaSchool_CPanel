<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin pastes a video link (YouTube, Facebook, Instagram, or any other
 * hosted video URL) instead of uploading a file — keeps the site light.
 * orientation is set by hand since it can't be reliably detected from a
 * link alone; autoplay is muted-on-load where the platform allows it
 * (Instagram's embed never supports it — see HomeVideo::resolveEmbed()).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('home_videos')) {
            return;
        }

        Schema::create('home_videos', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('video_url', 500);
            $table->string('orientation', 20)->default('landscape');
            $table->boolean('autoplay')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('home_videos');
    }
};
