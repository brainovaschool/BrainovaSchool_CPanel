<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('decision_rules', function (Blueprint $table) {
            $table->id();
            // 'leave' is the only working category today; the field this
            // string takes drives which show_* columns below apply, since
            // different categories will eventually control different
            // fields.
            $table->string('category');
            // Whose record this rule is about (null = any role).
            $table->unsignedBigInteger('subject_role_id')->nullable();
            // Who is granted the extra visibility (null = any role).
            $table->unsignedBigInteger('viewer_role_id')->nullable();
            $table->boolean('show_replacement_day')->default(false);
            $table->boolean('show_half_day_full_day')->default(false);
            $table->timestamps();

            $table->foreign('subject_role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('viewer_role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('decision_rules');
    }
};
