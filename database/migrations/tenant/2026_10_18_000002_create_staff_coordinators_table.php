<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Who oversees whom -- a staff member (a teacher, say) can have more than
 *  one coordinator/supervisor, and a coordinator can oversee more than one
 *  staff member, so this is a plain many-to-many pivot rather than a single
 *  coordinator_id column on staff. Super Admin/Admin always see everyone
 *  regardless of this table -- it only scopes what a coordinator sees. */
return new class extends Migration
{
    public function up()
    {
        Schema::create('staff_coordinators', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('coordinator_staff_id');
            $table->timestamps();

            $table->unique(['staff_id', 'coordinator_staff_id']);
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('coordinator_staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('staff_coordinators');
    }
};
