<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reel_characters')) {
            return;
        }

        Schema::create('reel_characters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('portrait_upload_id')->nullable()->comment('uploads.id');
            $table->longText('appearance_text')->nullable()->comment('visual appearance & consistency');
            $table->longText('voice_profile_text')->nullable();
            $table->longText('rules_text')->nullable()->comment('character-specific rules/behaviour');
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reel_characters');
    }
};
