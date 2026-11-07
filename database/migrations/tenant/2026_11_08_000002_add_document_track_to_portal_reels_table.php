<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** A "document track" reel skips Reel Studio entirely - the admin gives
 *  the assignee a prompt document (prompt_upload_id, already existed)
 *  plus whatever characters are on the reel's own Characters field
 *  (already existed too, via custom_fields), and the assignee goes
 *  straight to submitting a Drive video link. Every existing reel
 *  defaults to false, so nothing already in the Reel Studio pipeline
 *  changes behaviour. */
return new class extends Migration
{
    public function up()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'is_document_track')) {
                $table->boolean('is_document_track')->default(false)->after('reel_project_id');
            }
        });
    }

    public function down()
    {
        Schema::table('portal_reels', function (Blueprint $table) {
            if (Schema::hasColumn('portal_reels', 'is_document_track')) {
                $table->dropColumn('is_document_track');
            }
        });
    }
};
