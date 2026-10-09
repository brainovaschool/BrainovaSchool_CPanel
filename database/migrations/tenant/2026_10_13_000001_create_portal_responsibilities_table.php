<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Handoff spec phase 5. A responsibility is independent of the Role
 *  system on purpose — per the spec, "Content coordinator is not an
 *  account type, it is a responsibility the admin can give to any
 *  employee and move to someone else at any time." `key` marks the two
 *  built-in duties (content, audience) that later phases check rights
 *  against; an admin-added custom duty has no key and, per business
 *  rule, can be deleted — a keyed one can't. owner_staff_id null means
 *  the admin is currently holding it themselves. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_responsibilities')) {
            Schema::create('portal_responsibilities', function (Blueprint $table) {
                $table->id();
                $table->string('key', 30)->nullable()->unique(); // content | audience | null (custom)
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('freq', 10)->default('daily'); // daily | weekly
                $table->foreignId('owner_staff_id')->nullable()->constrained('staff')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_responsibilities');
    }
};
