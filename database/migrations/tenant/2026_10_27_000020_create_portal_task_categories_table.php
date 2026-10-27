<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/** A Task's `category` stays a plain string (not a foreign key - same
 *  reasoning as PortalReel.series_name) so nothing existing breaks; this
 *  table lets a category also carry an SOP (instructions text and/or an
 *  attached file), matched against portal_tasks.category by name. Seeded
 *  one-time from the existing flat "Task categories" setting list, so
 *  every category already in use shows up here ready to have an SOP
 *  attached, rather than starting empty. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_task_categories')) {
            Schema::create('portal_task_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('sop_text')->nullable();
                $table->unsignedBigInteger('sop_upload_id')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('portal_settings') && DB::table('portal_task_categories')->count() === 0) {
            $row = DB::table('portal_settings')->first();
            $names = $row && $row->categories ? (json_decode($row->categories, true) ?: []) : [];
            foreach (array_values(array_unique($names)) as $name) {
                $name = trim($name);
                if ($name === '') {
                    continue;
                }
                DB::table('portal_task_categories')->insert([
                    'name'       => $name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_task_categories');
    }
};
