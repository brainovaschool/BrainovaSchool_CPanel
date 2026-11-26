<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A second, independent "sent" flag alongside meeting_reminder_sent_at -
// the two reminders (a day before, an hour before) are tracked separately
// so each only ever fires once, on its own schedule, see
// AssessmentMeetingReminderCron.
return new class extends Migration
{
    public function up()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_leads', 'meeting_day_before_sent_at')) {
                $table->timestamp('meeting_day_before_sent_at')->nullable()->after('meeting_link');
            }
        });
    }

    public function down()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            $table->dropColumn(['meeting_day_before_sent_at']);
        });
    }
};
