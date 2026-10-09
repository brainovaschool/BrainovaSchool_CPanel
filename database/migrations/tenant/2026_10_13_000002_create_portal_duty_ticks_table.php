<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** One row per completed period of a responsibility — today's date for a
 *  Daily duty, that week's Monday for a Weekly one. The unique index is
 *  what makes ticking idempotent: a duty can't be marked done twice for
 *  the same period. */
return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('portal_duty_ticks')) {
            Schema::create('portal_duty_ticks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('responsibility_id')->constrained('portal_responsibilities')->cascadeOnDelete();
                $table->date('period');
                $table->unsignedBigInteger('ticked_by'); // users.id
                $table->timestamp('ticked_at');
                $table->timestamps();

                $table->unique(['responsibility_id', 'period']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('portal_duty_ticks');
    }
};
