<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// A lead created through the new simple registration form (no slot
// booked yet) has no assessment_slot_id at creation time - a slot only
// gets attached later, at the teacher-meeting-booking step. Raw SQL
// rather than the schema builder's ->change() so this doesn't depend
// on doctrine/dbal being installed; MySQL allows a NULL foreign key
// value without touching the constraint itself.
return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('assessment_leads')) {
            return;
        }
        DB::statement('ALTER TABLE assessment_leads MODIFY assessment_slot_id BIGINT UNSIGNED NULL');
    }

    public function down()
    {
        DB::statement('ALTER TABLE assessment_leads MODIFY assessment_slot_id BIGINT UNSIGNED NOT NULL');
    }
};
