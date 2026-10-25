<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** Social Board Settings used to be 5 fixed, hardcoded blocks (Platforms,
 *  Tracked numbers, Formats, Pillars, Series names), each its own named
 *  JSON column on portal_settings. This table replaces that with any
 *  number of freely add/rename/delete-able blocks instead. The 5 existing
 *  ones are carried over here (including anything already customized) so
 *  nothing is lost by the switch - see PortalSetting::getAttribute() for
 *  how existing callers (Reel/Audience forms) keep working unchanged. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_setting_lists')) {
            Schema::create('portal_setting_lists', function (Blueprint $table) {
                $table->id();
                // Stable identifier code looks a block up by (e.g. the Reel
                // form's Format dropdown reads the 'reel_formats' block) -
                // never shown to the admin, untouched by renaming the label.
                $table->string('key')->unique();
                $table->string('label');
                $table->json('values');
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('portal_settings') && DB::table('portal_setting_lists')->count() === 0) {
            $settingsRow = DB::table('portal_settings')->first();
            if ($settingsRow) {
                $blocks = [
                    ['key' => 'social_platforms', 'label' => 'Platforms', 'col' => 'social_platforms', 'fallback' => ['Instagram', 'Facebook', 'TikTok']],
                    ['key' => 'social_metrics', 'label' => 'Tracked numbers', 'col' => 'social_metrics', 'fallback' => ['Views', 'Followers', 'Likes']],
                    ['key' => 'reel_formats', 'label' => 'Formats', 'col' => 'reel_formats', 'fallback' => ['Reel', 'Carousel', 'Post']],
                    ['key' => 'reel_categories', 'label' => 'Reel/topic pillars', 'col' => 'reel_categories', 'fallback' => ['Educational', 'Promo', 'Entertainment', 'Behind the scenes']],
                    ['key' => 'series_names', 'label' => 'Series names', 'col' => 'series_names', 'fallback' => []],
                ];

                foreach ($blocks as $i => $b) {
                    $raw = $settingsRow->{$b['col']} ?? null;
                    $values = $raw ? json_decode($raw, true) : null;
                    if (!is_array($values) || empty($values)) {
                        $values = $b['fallback'];
                    }
                    DB::table('portal_setting_lists')->insert([
                        'key'        => $b['key'],
                        'label'      => $b['label'],
                        'values'     => json_encode(array_values($values)),
                        'sort_order' => $i + 1,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_setting_lists');
    }
};
