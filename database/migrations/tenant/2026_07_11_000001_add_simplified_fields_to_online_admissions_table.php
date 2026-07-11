<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            $table->string('student_age')->nullable()->after('last_name');
            $table->string('student_class')->nullable()->after('student_age');
            $table->string('program')->nullable()->after('student_class');
        });

        Schema::table('online_admissions', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')->nullable()->change();
            $table->unsignedBigInteger('classes_id')->nullable()->change();
            $table->unsignedBigInteger('section_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('online_admissions', function (Blueprint $table) {
            $table->dropColumn(['student_age', 'student_class', 'program']);
        });

        Schema::table('online_admissions', function (Blueprint $table) {
            $table->unsignedBigInteger('session_id')->nullable(false)->change();
            $table->unsignedBigInteger('classes_id')->nullable(false)->change();
            $table->unsignedBigInteger('section_id')->nullable(false)->change();
        });
    }
};
