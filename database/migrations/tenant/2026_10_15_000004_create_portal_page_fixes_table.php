<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Page-audit fixes — a simple checklist with an owner, per the spec's
 *  "page fixes status and owner" under Board settings. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_page_fixes')) {
            Schema::create('portal_page_fixes', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('status', 20)->default('open'); // open | in_progress | fixed
                $table->foreignId('owner_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->unsignedBigInteger('created_by'); // users.id
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_page_fixes');
    }
};
