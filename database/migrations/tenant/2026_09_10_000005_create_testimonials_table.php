<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('testimonials')) {
            return;
        }

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('testimonial')->comment('testimonial | review');
            $table->string('name');
            $table->string('role')->nullable()->comment('e.g. Parent, Grade 5');
            $table->text('quote');
            $table->unsignedTinyInteger('rating')->nullable()->comment('1-5');
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('testimonials');
    }
};
