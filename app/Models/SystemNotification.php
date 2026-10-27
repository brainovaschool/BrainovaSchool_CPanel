<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SystemNotification extends BaseModel
{
    use HasFactory;

    private const KEEP_PER_USER = 20;

    /** Keeps each person's notification history capped at KEEP_PER_USER -
     *  every new one triggers a cleanup of whatever's older than the most
     *  recent 20 for that same reciver_id, so the table never grows
     *  without bound and nobody has to remember to prune it. */
    protected static function boot()
    {
        parent::boot();

        static::created(function (self $notification) {
            if (!$notification->reciver_id) {
                return;
            }
            $keepIds = static::where('reciver_id', $notification->reciver_id)
                ->orderByDesc('id')
                ->limit(self::KEEP_PER_USER)
                ->pluck('id');
            static::where('reciver_id', $notification->reciver_id)
                ->whereNotIn('id', $keepIds)
                ->delete();
        });
    }

    public static function myNotification(){
        if(Auth::check()){
            return Auth::user()->unreadNotifications;
        }else{
            return [];
        }
    }

    /** The list actually shown in the bell/dashboard — read notifications
     *  stay visible (styled differently), only the unread count comes
     *  from myNotification()/unreadNotifications(). */
    public static function recentNotifications(){
        if(Auth::check()){
            return Auth::user()->recentNotifications;
        }else{
            return [];
        }
    }
}
