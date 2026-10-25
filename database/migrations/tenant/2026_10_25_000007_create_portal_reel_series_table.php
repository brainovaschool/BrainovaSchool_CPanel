<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** Series stops being a plain name on the "Series names" Settings block
 *  and becomes its own small table - each series carries default Pillar/
 *  Format/Character values, so picking a series on the Reel form can
 *  auto-fill the rest. The one-time seed below carries over whatever
 *  series names already exist on that block (no defaults set yet - the
 *  admin fills those in on the new Series management page), so nothing
 *  already typed in is lost by the switch. The "Series names" block
 *  itself is left as-is, just no longer read by the Reel form. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_reel_series')) {
            Schema::create('portal_reel_series', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('default_category')->nullable();  // Pillar
                $table->string('default_format')->nullable();
                $table->string('default_character')->nullable(); // a reel_characters.name
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('portal_setting_lists') && DB::table('portal_reel_series')->count() === 0) {
            $row = DB::table('portal_setting_lists')->where('key', 'series_names')->first();
            if ($row) {
                $names = json_decode($row->values, true) ?: [];
                foreach (array_values($names) as $i => $name) {
                    DB::table('portal_reel_series')->insert([
                        'name'       => $name,
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
        Schema::dropIfExists('portal_reel_series');
    }
};
