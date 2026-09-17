<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reflection_journal_entries')) {
            return;
        }

        Schema::create('reflection_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->date('entry_date');
            $table->text('what_was_hard')->nullable();
            $table->text('what_worked')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'entry_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reflection_journal_entries');
    }
};
