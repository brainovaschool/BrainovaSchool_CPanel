<?php

namespace App\Services\Portal;

use App\Models\SystemNotification;

/** Team Portal notifications reuse the LMS's existing bell/table exactly
 *  as is (SystemNotification, the same one the rest of the admin panel
 *  already shows) rather than the heavier NotificationSetting template
 *  system — that layer is built around broadcasting to a whole role
 *  (Student, Parent, Teacher) through admin-edited email/SMS/web
 *  templates, which doesn't fit "notify this one specific person about
 *  this one specific task." A direct insert is what send_web() itself
 *  does under the hood anyway. */
class PortalNotifier
{
    public static function send(?int $userId, string $title, string $message, ?string $url = null): void
    {
        if (!$userId) {
            return;
        }

        // Direct property assignment, not ::create() — matches
        // SendNotificationTrait::send_web()'s own proven pattern, since
        // SystemNotification (via BaseModel) sets no $fillable/$guarded
        // override and so mass assignment can't be relied on here.
        $notification = new SystemNotification();
        $notification->title      = $title;
        $notification->message    = $message;
        $notification->reciver_id = $userId;
        $notification->url        = $url;
        $notification->save();
    }

    /** Guards the scheduled deadline/overdue job against sending the same
     *  notice to the same person for the same task more than once a day. */
    public static function alreadySentToday(int $userId, string $title, string $url): bool
    {
        return SystemNotification::where('reciver_id', $userId)
            ->where('title', $title)
            ->where('url', $url)
            ->whereDate('created_at', now()->format('Y-m-d'))
            ->exists();
    }
}
