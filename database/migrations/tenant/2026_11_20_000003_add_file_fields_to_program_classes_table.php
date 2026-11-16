<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Project/Quiz/Worksheet/Assignment could only ever be a plain typed
 *  link - this gives each the same storage_key/original_filename pair
 *  Materials already has, so a teacher can upload a file directly
 *  instead of (or alongside) pasting a link, same B2Storage convention
 *  used everywhere else in this app. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('program_classes', function (Blueprint $table) {
            foreach (['project', 'quiz', 'worksheet', 'assignment'] as $type) {
                if (!Schema::hasColumn('program_classes', "{$type}_storage_key")) {
                    $table->string("{$type}_storage_key")->nullable()->after("{$type}_link_url");
                    $table->string("{$type}_original_filename")->nullable()->after("{$type}_storage_key");
                }
            }
        });
    }

    public function down()
    {
        Schema::table('program_classes', function (Blueprint $table) {
            foreach (['project', 'quiz', 'worksheet', 'assignment'] as $type) {
                foreach (["{$type}_storage_key", "{$type}_original_filename"] as $col) {
                    if (Schema::hasColumn('program_classes', $col)) {
                        $table->dropColumn($col);
                    }
                }
            }
        });
    }
};
