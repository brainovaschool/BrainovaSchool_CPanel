<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_category_id')->constrained('program_categories')->cascadeOnDelete();
            $table->foreignId('program_focus_id')->nullable()->constrained('program_focuses')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('badge')->nullable();
            $table->text('description')->nullable();
            $table->string('age_range')->nullable();
            $table->string('grade')->nullable();
            $table->string('lessons')->nullable();
            $table->string('duration')->nullable();
            $table->string('enrolled')->nullable();
            $table->string('price')->nullable();
            $table->string('accent')->default('teal');
            $table->foreignId('upload_id')->nullable()->constrained('uploads')->nullOnDelete();
            $table->string('image_url')->nullable()->comment('external image fallback when no upload');
            $table->text('meta_description')->nullable();
            $table->json('overview')->nullable()->comment('array of paragraphs');
            $table->json('highlights')->nullable()->comment('array of bullet points');
            $table->text('format')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('programs');
    }
};
