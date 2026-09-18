<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per student: which avatar look + accessory they've equipped, the
 * name they gave their avatar, and their chosen speaking-voice preset (a
 * rate/pitch pair applied to the browser's own text-to-speech — no external
 * voice service, same zero-AI-dependency rule as the rest of the character
 * system).
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_avatar_profiles')) {
            return;
        }

        Schema::create('student_avatar_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained('students')->cascadeOnDelete();
            $table->string('avatar_name', 40)->nullable();
            $table->foreignId('avatar_item_id')->nullable()->constrained('avatar_items')->nullOnDelete();
            $table->foreignId('accessory_item_id')->nullable()->constrained('avatar_items')->nullOnDelete();
            $table->string('voice_preset', 20)->default('cheerful');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_avatar_profiles');
    }
};
