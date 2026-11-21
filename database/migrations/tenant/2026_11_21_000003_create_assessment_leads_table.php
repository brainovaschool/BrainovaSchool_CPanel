<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A prospective student who booked an assessment, but has NOT been
// registered as a real Student/User/ParentGuardian - deliberately a
// flat, standalone table (same spirit as the existing `contacts`
// table), not a relation on the student_info tables, so nobody shows
// up anywhere in the school's real roster until they actually enrol.
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('assessment_leads')) {
            return;
        }

        Schema::create('assessment_leads', function (Blueprint $table) {
            $table->id();
            $table->string('guardian_name');
            $table->string('student_name');
            $table->string('student_age', 40)->nullable();
            $table->unsignedTinyInteger('grade');
            $table->string('syllabus', 60)->comment('SNC, Cambridge, Oxford, Pearson Edexcel, Other');
            $table->string('subject', 60);
            $table->string('email');
            $table->string('whatsapp_number', 40);
            $table->string('city', 100)->nullable();
            $table->text('message')->nullable();

            $table->foreignId('assessment_slot_id')->constrained('assessment_slots');

            // The id this lead is known by inside the standalone Assessment
            // System / Google Sheet (a LEAD-{id} pseudo id - never a real
            // students.id or users.id).
            $table->string('assessment_system_student_id')->nullable();
            $table->string('assigned_assessment_id')->nullable();
            $table->string('assigned_assessment_title')->nullable();
            $table->text('assessment_link')->nullable();
            $table->timestamp('link_sent_at')->nullable();
            $table->timestamp('whatsapp_sent_at')->nullable()->comment('reserved for a later phase - not populated yet');

            // new -> invited (link sent) -> tested -> reported (pathway
            // shared) -> booked_meeting -> enrolled. Only new/invited are
            // ever set by this phase; the rest are reserved for later ones.
            $table->string('status', 30)->default('new');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('assessment_leads');
    }
};
