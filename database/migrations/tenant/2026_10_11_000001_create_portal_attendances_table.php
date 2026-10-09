<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** First-login-of-the-day attendance for Team Portal employees (handoff
 *  spec phase 3, business rule 6). Recorded by a Login-event listener, not
 *  by any change to the actual auth/login code paths — see
 *  App\Listeners\Portal\RecordStaffAttendance. One row per staff per day,
 *  enforced by the unique index, so a second login the same day is a
 *  silent no-op rather than a duplicate. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_attendances')) {
            Schema::create('portal_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
                $table->date('date');
                $table->timestamp('first_login_at');
                $table->timestamps();

                $table->unique(['staff_id', 'date']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_attendances');
    }
};
