<?php

use App\Models\Upload;
use App\Models\WebsiteSetup\PageSections;
use Illuminate\Database\Migrations\Migration;

/** Same pattern as beyond_academics_overview_hero - a normal row in the
 *  existing page_sections table, editable from Website Setup > Sections
 *  with no extra UI. Uses the same Brainbot image as Beyond Academics'
 *  banner, as asked, with its own title/subtitle since Core Academics
 *  (academic subjects) is a different domain from Beyond Academics
 *  (coding/AI/robotics etc). */
return new class extends Migration
{
    public function up()
    {
        if (PageSections::where('key', 'core_academics_overview_hero')->exists()) {
            return;
        }

        $upload = new Upload();
        $upload->path = 'frontend/img/mascots/brainbot.png';
        $upload->save();

        $row = new PageSections();
        $row->key = 'core_academics_overview_hero';
        $row->name = 'Strong Foundations, Real Results';
        $row->description = 'Core Subjects • Structured Curriculum • Measurable Progress';
        $row->upload_id = $upload->id;
        $row->data = [];
        $row->save();
    }

    public function down()
    {
        PageSections::where('key', 'core_academics_overview_hero')->delete();
    }
};
