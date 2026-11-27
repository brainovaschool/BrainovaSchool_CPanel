<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tracks which of a lead's assigned subjects have actually been
// submitted (a JSON array of attempt ids), so the "see your result"
// email only ever goes out once every assigned subject is done, not
// after just the first one - see AssessmentBookingController::completed().
return new class extends Migration
{
    public function up()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            if (! Schema::hasColumn('assessment_leads', 'completed_attempt_ids')) {
                $table->text('completed_attempt_ids')->nullable()->after('assigned_assessment_title');
            }
        });
    }

    public function down()
    {
        Schema::table('assessment_leads', function (Blueprint $table) {
            $table->dropColumn(['completed_attempt_ids']);
        });
    }
};
