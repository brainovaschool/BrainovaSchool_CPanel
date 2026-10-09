<?php

namespace App\Listeners\Portal;

use App\Models\Portal\PortalAttendance;
use Illuminate\Auth\Events\Login;

/** "Attendance is the first sign-in of the day" (handoff spec, business
 *  rule 6) — listens to Laravel's own Login event instead of touching
 *  AuthenticationRepository::login(), so every existing login path
 *  (Student, Parent, Staff, whatever comes later) keeps working exactly
 *  as before; this only ever reads $event->user afterwards. Only accounts
 *  with a Staff row count as Team Portal employees — Students and
 *  Guardians never have one, so they're silently skipped. */
class RecordStaffAttendance
{
    public function handle(Login $event): void
    {
        $staff = $event->user->staff ?? null;
        if (!$staff) {
            return;
        }

        PortalAttendance::firstOrCreate(
            ['staff_id' => $staff->id, 'date' => now()->format('Y-m-d')],
            ['first_login_at' => now()]
        );
    }
}
