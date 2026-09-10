<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('trial_slots')) {
            return;
        }

        Schema::create('trial_slots', function (Blueprint $table) {
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
        Schema::dropIfExists('trial_slots');
    }
};
