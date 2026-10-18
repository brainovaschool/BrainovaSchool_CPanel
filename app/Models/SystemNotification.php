<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SystemNotification extends BaseModel
{
    use HasFactory;


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
