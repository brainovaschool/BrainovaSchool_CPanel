<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('reel_projects')) {
            return;
        }

        Schema::create('reel_projects', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('topic')->nullable();
            $table->unsignedBigInteger('created_by')->comment('users.id — the teacher who owns this project');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reel_projects');
    }
};
