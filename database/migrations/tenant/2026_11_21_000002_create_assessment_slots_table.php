<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Deliberately a separate table from trial_slots, not a reuse of it -
// this app's "book a free trial" slots stay untouched for their own
// purpose; these are for booking a sitting of the Assessment System.
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('assessment_slots')) {
            return;
        }

        Schema::create('assessment_slots', function (Blueprint $table) {
            $table->id();
            $table->date('slot_date');
            $table->string('start_time', 20)->comment('e.g. 10:00 AM');
            $table->string('end_time', 20)->nullable();
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->unsignedSmallInteger('booked_count')->default(0);
            $table->string('note')->nullable();
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();

            $table->index(['slot_date', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessment_slots');
    }
};
