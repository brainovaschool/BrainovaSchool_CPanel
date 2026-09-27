<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One mission = one story wrapped around one real assignment. hub_id is
 * the Base (subject zone) it belongs to; building_id is the Building it
 * unlocks when finished (nullable — a mission doesn't have to unlock
 * anything, e.g. a bonus side quest). theme matches AvatarItem's spirit —
 * a plain string, not a foreign key, since themes are a fixed short list
 * (mystery/detective/adventure/rescue/treasure), not admin-created content.
 *
 * clue_lines is a JSON array of up to 7 short strings — the flavour text
 * for the fixed intro/clue-steps/ending shape (see the product plan,
 * Section 7.2). Progress through them is never stored per student: it's
 * computed live from how far along the linked assignment the student is
 * (linkable_type/linkable_id, e.g. a Homework or an OnlineExam), so a
 * mission is never out of sync with the real gradebook.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('missions')) {
            return;
        }

        Schema::create('missions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hub_id')->constrained('avatar_items')->cascadeOnDelete();
            $table->foreignId('building_id')->nullable()->constrained('avatar_items')->nullOnDelete();
            $table->string('theme', 30);
            $table->string('character', 30)->default('brainbot');
            $table->string('title');
            $table->text('intro_line');
            $table->json('clue_lines');
            $table->text('ending_line');
            $table->string('reward_note')->nullable();
            $table->string('linkable_type', 30)->nullable();
            $table->unsignedBigInteger('linkable_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('missions');
    }
};
