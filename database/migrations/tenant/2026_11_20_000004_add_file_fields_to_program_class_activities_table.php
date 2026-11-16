<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Same storage_key/original_filename pair ProgramClassMaterial already
 *  has, so an Activity can carry an uploaded file alongside (or instead
 *  of) its typed-in link_url. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('program_class_activities')) {
            return;
        }

        Schema::table('program_class_activities', function (Blueprint $table) {
            if (!Schema::hasColumn('program_class_activities', 'storage_key')) {
                $table->string('storage_key')->nullable()->after('link_url');
                $table->string('original_filename')->nullable()->after('storage_key');
            }
        });
    }

    public function down()
    {
        Schema::table('program_class_activities', function (Blueprint $table) {
            foreach (['storage_key', 'original_filename'] as $col) {
                if (Schema::hasColumn('program_class_activities', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
