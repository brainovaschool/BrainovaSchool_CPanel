<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Watch & Learn: topic tabs (Science Facts, Hacks, Young Entrepreneurs...)
 *  across the top, and two always-separate zones underneath — landscape
 *  videos and portrait videos never mix in the same row, since each
 *  zone's cards share one aspect ratio. Each video sits inside an
 *  admin-picked "tile" (a decorative frame image uploaded once, reused
 *  across many videos) — orientation on both the template and the video
 *  keeps a portrait video from ever being offered a landscape frame. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('watch_learn_tabs')) {
            Schema::create('watch_learn_tabs', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('watch_learn_templates')) {
            Schema::create('watch_learn_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('orientation', 20)->default('landscape');
                $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('watch_learn_videos')) {
            Schema::create('watch_learn_videos', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tab_id')->nullable()->constrained('watch_learn_tabs')->nullOnDelete();
                $table->foreignId('template_id')->nullable()->constrained('watch_learn_templates')->nullOnDelete();
                $table->string('title')->nullable();
                $table->string('video_url', 500);
                $table->string('orientation', 20)->default('landscape');
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('watch_learn_videos');
        Schema::dropIfExists('watch_learn_templates');
        Schema::dropIfExists('watch_learn_tabs');
    }
};
