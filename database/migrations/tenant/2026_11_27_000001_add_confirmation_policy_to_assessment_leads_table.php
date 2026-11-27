<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Supports the "confirm at least 24 hours before your meeting, or the
// slot may go to someone else" policy: meeting_confirm_deadline is set
// once, at booking time (see MeetingBookingController::store()) - never
// recomputed later, so it stays a fixed promise regardless of when the
// reminder cron happens to run. meeting_released_at records that this
// lead's slot was freed back up because they never confirmed in time
// (see AssessmentMeetingReminderCron), distinct from an ordinary
// "not yet reminded" lead that's still within its window.
return new class extends Migration
{
    public function up()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_leads', 'meeting_confirm_deadline')) {
                $table->timestamp('meeting_confirm_deadline')->nullable()->after('meeting_confirmed_at');
            }
            if (! Schema::hasColumn('assessment_leads', 'meeting_released_at')) {
                $table->timestamp('meeting_released_at')->nullable()->after('meeting_confirm_deadline');
            }
        });
    }

    public function down()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            $table->dropColumn(['meeting_confirm_deadline', 'meeting_released_at']);
        });
    }
};
