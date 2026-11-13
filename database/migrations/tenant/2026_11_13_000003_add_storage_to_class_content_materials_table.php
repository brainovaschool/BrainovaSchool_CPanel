<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A material can now be an uploaded file (stored on Backblaze, not on this
 *  server) instead of only a pasted link - storage_key is the file's path
 *  inside the bucket. url stays for existing link-based materials and is
 *  now optional, since a file-based material has no link at all. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('class_content_materials', function (Blueprint $table) {
            if (!Schema::hasColumn('class_content_materials', 'storage_key')) {
                $table->string('storage_key', 500)->nullable()->after('url');
            }
            if (!Schema::hasColumn('class_content_materials', 'original_filename')) {
                $table->string('original_filename')->nullable()->after('storage_key');
            }
        });

        Schema::table('class_content_materials', function (Blueprint $table) {
            $table->string('url', 500)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('class_content_materials', function (Blueprint $table) {
            $table->dropColumn(['storage_key', 'original_filename']);
        });
    }
};
