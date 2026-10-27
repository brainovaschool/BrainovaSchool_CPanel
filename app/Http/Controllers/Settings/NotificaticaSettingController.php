<?php

namespace App\Http\Controllers\Settings;

use Illuminate\Http\Request;
use App\Models\SystemNotification;
use App\Models\NotificationSetting;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Repositories\SystemNotificationRepository;

class NotificaticaSettingController extends Controller
{

    protected $system_notification_repo;


    public function __construct(SystemNotificationRepository $system_notification_repo)
    {
        $this->system_notification_repo = $system_notification_repo;
    }

    public function notificationSettings(Request $request){
        $data = [];
        $data['pt'] = ___('settings.notification_setting');
        $data['notificationSettings'] =  $this->system_notification_repo->setting();
        // Only people who actually have notifications - no point listing
        // everyone in a "manage notifications" picker.
        $data['notifiedUsers'] = User::whereIn('id', SystemNotification::select('reciver_id')->distinct())
            ->orderBy('name')->get();
        $data['selectedUserId'] = $request->integer('user_id') ?: null;
        $data['selectedUserNotifications'] = $data['selectedUserId']
            ? $this->system_notification_repo->userNotifications($data['selectedUserId'])
            : collect();
        return view('backend.settings.notification_setting',$data);
    }

    /** Admin can delete anyone's notification, one at a time - stays on
     *  the same person's list afterward. */
    public function deleteUserNotification(Request $request, $id){
        $this->system_notification_repo->deleteNotification((int) $id);
        return redirect()->route('settings.notification-settings', ['user_id' => $request->integer('user_id')])->with('success', 'Notification deleted.');
    }

    public function clearUserNotifications(Request $request, $userId){
        $this->system_notification_repo->deleteAllForUser((int) $userId);
        return redirect()->route('settings.notification-settings', ['user_id' => $userId])->with('success', 'All notifications cleared for this person.');
    }


    public function notificationEventModal($id , $key){

        $modal = $this->system_notification_repo->showSetting($id);
        $data = [];
        if($modal){
            $data['id'] = $id;
            $data['key'] = $key;
            $data['shortcode'] = $modal->shortcode[$key];
            $data['subject'] = $modal->subject[$key];
            $data['emailBody'] = $modal->template[$key]['Email'];
            $data['smsBody'] = $modal->template[$key]['SMS'];
            $data['appBody'] = $modal->template[$key]['App'];
            $data['webBody'] = $modal->template[$key]['Web'];
        }
        return view('backend.settings.notification_setting_modal',$data);
    }

    public function viewNotification($id){

         $result = $this->system_notification_repo->readNotification($id);
         if($result && $result->url){
            return redirect($result->url);
         }

         return redirect()->back()->with('success', 'Notification Viewed');
    }


    public function updateNotificationSetting(Request $request){

        $result = $this->system_notification_repo->settingUpdate($request);
    }
}
