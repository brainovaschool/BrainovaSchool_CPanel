<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ownership + purchase history. A row here means the student owns that
 * avatar_item permanently — price_paid is recorded so a later price change
 * in the catalogue never rewrites history.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_avatar_purchases')) {
            return;
        }

        Schema::create('student_avatar_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('avatar_item_id')->constrained('avatar_items')->cascadeOnDelete();
            $table->unsignedInteger('price_paid')->default(0);
            $table->timestamps();

            $table->unique(['student_id', 'avatar_item_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('student_avatar_purchases');
    }
};
