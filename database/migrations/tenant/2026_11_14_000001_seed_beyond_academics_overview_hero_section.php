<?php

use App\Models\Upload;
use App\Models\WebsiteSetup\PageSections;
use Illuminate\Database\Migrations\Migration;

/** One row in the existing, already-admin-editable page_sections table
 *  (Website Setup -> Sections) - name/description/upload_id are shown and
 *  editable there automatically for any key, with no extra UI needed.
 *  Defaults to the Brainbot mascot until an admin uploads something else. */
return new class extends Migration
{
    public function up()
    {
        if (PageSections::where('key', 'beyond_academics_overview_hero')->exists()) {
            return;
        }

        $upload = new Upload();
        $upload->path = 'frontend/img/mascots/brainbot.png';
        $upload->save();

        $row = new PageSections();
        $row->key = 'beyond_academics_overview_hero';
        $row->name = 'Learn New Skills, Explore New Possibilities';
        $row->description = 'Short courses • Practical Skills • Lifelong Learning';
        $row->upload_id = $upload->id;
        $row->data = [];
        $row->save();
    }

    public function down()
    {
        PageSections::where('key', 'beyond_academics_overview_hero')->delete();
    }
};
