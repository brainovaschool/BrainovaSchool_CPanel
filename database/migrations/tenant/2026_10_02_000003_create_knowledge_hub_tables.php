<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Knowledge Hub: admin creates any number of pages (e.g. "Study Tips",
 *  "For Parents"), each holding any number of topics — a title plus an
 *  explanation, shown like a simple article/FAQ list. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('knowledge_hub_pages')) {
            Schema::create('knowledge_hub_pages', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('slug')->unique();
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('knowledge_hub_topics')) {
            Schema::create('knowledge_hub_topics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('knowledge_hub_pages')->cascadeOnDelete();
                $table->string('title');
                $table->longText('explanation');
                $table->unsignedInteger('sort_order')->default(0);
                $table->tinyInteger('status')->default(App\Enums\Status::ACTIVE);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('knowledge_hub_topics');
        Schema::dropIfExists('knowledge_hub_pages');
    }
};
