<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('dashboard_features')) {
            return;
        }

        Schema::create('dashboard_features', function (Blueprint $table) {
            $table->id();
            $table->string('portal', 20); // student | teacher | parent
            $table->string('feature_key', 60);
            $table->string('label');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedTinyInteger('status')->default(1);
            $table->timestamps();

            $table->unique(['portal', 'feature_key']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('dashboard_features');
    }
};
