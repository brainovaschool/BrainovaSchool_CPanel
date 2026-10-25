<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Admin-defined extra fields on the Add/Edit Reel form - text box,
 *  paragraph, dropdown, checkbox, date or number. A dropdown field's
 *  options come from a named block on Social Board Settings
 *  (portal_setting_lists), picked by that block's key. Submitted values
 *  live in portal_reels.custom_fields, keyed by this field's key. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_reel_custom_fields')) {
            Schema::create('portal_reel_custom_fields', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('label');
                $table->string('type', 20); // text | textarea | select | checkbox | date | number
                // Only meaningful for type = select - the portal_setting_lists.key
                // this dropdown's options come from.
                $table->string('list_key')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('portal_reels', function (Blueprint $table) {
            if (!Schema::hasColumn('portal_reels', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('note');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('portal_reel_custom_fields');
        Schema::table('portal_reels', function (Blueprint $table) {
            if (Schema::hasColumn('portal_reels', 'custom_fields')) {
                $table->dropColumn('custom_fields');
            }
        });
    }
};
