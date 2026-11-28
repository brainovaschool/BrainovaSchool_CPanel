<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A plain-text copy of the booked slot's date/time, set once at booking
// time (see MeetingBookingController::store()) and never cleared. The
// admin leads list normally reads the live slot via assessment_slot_id,
// but AssessmentSlotRepository::cleanupPast() deletes old, used-up
// slots and detaches the leads that pointed at them - this is what the
// leads list falls back to showing so that history isn't lost just
// because the slot row itself is gone.
return new class extends Migration
{
    public function up()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_leads', 'booked_slot_label')) {
                $table->string('booked_slot_label')->nullable()->after('assessment_slot_id');
            }
        });
    }

    public function down()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            $table->dropColumn(['booked_slot_label']);
        });
    }
};
