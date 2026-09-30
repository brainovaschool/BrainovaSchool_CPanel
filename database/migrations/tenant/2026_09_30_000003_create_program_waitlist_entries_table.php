<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A family joining the waitlist for a program that hasn't started yet
 * (Homeschooling, Social Clubs — see the website fix list, items H1/H12).
 * Deliberately separate from the existing free-trial/demo-class booking
 * table: that one drives real slot scheduling for a program that's live
 * today, and reusing it for "notify me when this launches" would mean
 * bending its slot/validation logic to a case it was never meant for.
 */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('program_waitlist_entries')) {
            return;
        }

        Schema::create('program_waitlist_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_category_id')->nullable()->constrained('program_categories')->nullOnDelete();
            $table->string('parent_name');
            $table->string('whatsapp_number', 40);
            $table->string('child_grade', 80)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('program_waitlist_entries');
    }
};
