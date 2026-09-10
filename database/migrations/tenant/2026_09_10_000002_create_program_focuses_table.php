<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('program_focuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_category_id')->constrained('program_categories')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();

            $table->unique(['program_category_id', 'slug']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('program_focuses');
    }
};
