<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Handoff spec phase 8 — reel/topic pipeline. Storage only against the
 *  rest of the LMS: thumbnails and prompt files reuse the existing
 *  Upload model, nothing else is touched. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_reels')) {
            Schema::create('portal_reels', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('format', 20)->default('Reel'); // Reel | Carousel | Post
                $table->string('category')->nullable();
                $table->text('hook')->nullable();
                $table->string('status', 20)->default('suggested'); // suggested | accepted | production | ready | published | rejected

                $table->unsignedBigInteger('suggested_by'); // users.id
                $table->unsignedBigInteger('created_by');   // users.id

                $table->date('planned_date')->nullable();
                $table->string('drive_link')->nullable();
                $table->text('prompt_text')->nullable();
                $table->unsignedBigInteger('prompt_upload_id')->nullable();
                $table->string('prompt_link')->nullable();
                $table->unsignedBigInteger('thumb_upload_id')->nullable();
                $table->text('note')->nullable();

                $table->unsignedBigInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();

                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_reels');
    }
};
