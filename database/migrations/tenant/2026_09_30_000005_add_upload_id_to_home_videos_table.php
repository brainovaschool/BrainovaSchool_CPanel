<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Lets the admin upload a video file directly as an alternative to
 *  pasting a link — video_url becomes optional, exactly one of the two
 *  is expected (enforced in HomeVideoController::validateRequest()). */
return new class extends Migration
{
    public function up()
    {
        if (Schema::hasColumn('home_videos', 'upload_id')) {
            return;
        }

        Schema::table('home_videos', function (Blueprint $table) {
            $table->foreignId('upload_id')->nullable()->after('id')->constrained('uploads')->nullOnDelete();
        });

        Schema::table('home_videos', function (Blueprint $table) {
            $table->string('video_url', 500)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('home_videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('upload_id');
        });
    }
};
