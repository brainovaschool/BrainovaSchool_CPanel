<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Collapses the three narrow default_category/default_format/
 *  default_character columns into one JSON `defaults` column -
 *  {category, format, custom_fields: {field_key: value}} - so a series
 *  can default EVERY Reel form field (not just Pillar/Format/one
 *  Character), the same way a reel's own `custom_fields` column already
 *  stores arbitrary field values. The Characters field now stores an
 *  array (a series, and a reel, can carry more than one character). */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('portal_reel_series', 'defaults')) {
            Schema::table('portal_reel_series', function (Blueprint $table) {
                $table->json('defaults')->nullable()->after('name');
            });
        }

        if (Schema::hasColumn('portal_reel_series', 'default_category')) {
            $charactersFieldKey = DB::table('portal_reel_custom_fields')
                ->where('list_key', '__reel_characters__')
                ->value('key');

            foreach (DB::table('portal_reel_series')->get() as $row) {
                $customFields = [];
                if ($charactersFieldKey && $row->default_character) {
                    $customFields[$charactersFieldKey] = [$row->default_character];
                }
                DB::table('portal_reel_series')->where('id', $row->id)->update([
                    'defaults' => json_encode([
                        'category'      => $row->default_category,
                        'format'        => $row->default_format,
                        'custom_fields' => $customFields,
                    ]),
                ]);
            }

            Schema::table('portal_reel_series', function (Blueprint $table) {
                $table->dropColumn(['default_category', 'default_format', 'default_character']);
            });
        }
    }

    public function down()
    {
        Schema::table('portal_reel_series', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reel_series', 'default_category')) {
                $table->string('default_category')->nullable();
                $table->string('default_format')->nullable();
                $table->string('default_character')->nullable();
            }
            if (Schema::hasColumn('portal_reel_series', 'defaults')) {
                $table->dropColumn('defaults');
            }
        });
    }
};
