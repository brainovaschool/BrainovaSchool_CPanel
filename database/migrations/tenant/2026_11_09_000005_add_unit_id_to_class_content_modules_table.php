<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('class_content_modules', function (Blueprint $table) {
            // nullOnDelete, not cascade: deleting a Unit should only ungroup
            // its modules back to "no unit", never delete real class content
            // along with an organisational label.
            $table->foreignId('unit_id')->nullable()->after('id')->constrained('class_content_units')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('class_content_modules', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
