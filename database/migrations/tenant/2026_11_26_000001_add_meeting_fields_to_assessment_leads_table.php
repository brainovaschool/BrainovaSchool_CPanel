<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Supports the "book a meeting with the teacher" step: a generated video
// call link (Google Meet when Calendar is connected, a Jitsi fallback
// otherwise - see GoogleCalendarClient/MeetingBookingController), when the
// 1-hour-before reminder went out, and whether the parent tapped the
// "I'll be there" confirmation link in that reminder.
return new class extends Migration
{
    public function up()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_leads', 'meeting_link')) {
                $table->text('meeting_link')->nullable()->after('assessment_slot_id');
            }
            if (! Schema::hasColumn('assessment_leads', 'meeting_reminder_sent_at')) {
                $table->timestamp('meeting_reminder_sent_at')->nullable()->after('whatsapp_sent_at');
            }
            if (! Schema::hasColumn('assessment_leads', 'meeting_confirmed_at')) {
                $table->timestamp('meeting_confirmed_at')->nullable()->after('meeting_reminder_sent_at');
            }
        });
    }

    public function down()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            $table->dropColumn(['meeting_link', 'meeting_reminder_sent_at', 'meeting_confirmed_at']);
        });
    }
};
