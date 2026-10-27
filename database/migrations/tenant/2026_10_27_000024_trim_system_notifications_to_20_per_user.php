<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/** One-time cleanup for notifications that piled up before the 20-per-
 *  person cap (SystemNotification::boot()) started pruning automatically
 *  on every new notification. Trims each person's existing history down
 *  to their 20 most recent notifications. */
return new class extends Migration
{
    public function up()
    {
        $receiverIds = DB::table('system_notifications')->distinct()->pluck('reciver_id');

        foreach ($receiverIds as $receiverId) {
            if (!$receiverId) {
                continue;
            }

            $keepIds = DB::table('system_notifications')
                ->where('reciver_id', $receiverId)
                ->orderByDesc('id')
                ->limit(20)
                ->pluck('id');

            DB::table('system_notifications')
                ->where('reciver_id', $receiverId)
                ->whereNotIn('id', $keepIds->isEmpty() ? [0] : $keepIds)
                ->delete();
        }
    }

    public function down()
    {
        // Deleted rows can't be restored - nothing to reverse.
    }
};
