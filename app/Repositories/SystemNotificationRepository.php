<?php

namespace App\Repositories;

use Carbon\Carbon;
use App\Traits\CommonHelperTrait;
use App\Models\SystemNotification;
use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Log;
use App\Traits\ApiReturnFormatTrait;
use App\Interfaces\SystemNotificationInterface;

class SystemNotificationRepository implements SystemNotificationInterface{


    protected $model ;
    protected $notification_setting;

    use CommonHelperTrait;
    use ApiReturnFormatTrait;

    public function __construct(SystemNotification $model , NotificationSetting $notification_setting)
    {
        $this->model = $model;
        $this->notification_setting = $notification_setting;
    }

    public function show($id){
        return $this->model->findOrFail($id);
    }


    public function setting(){
        return $this->notification_setting->get();
    }

    public function showSetting($id){
        return $this->notification_setting->findOrFail($id);
    }


    public function settingUpdate($request){

        try {
            $id = $request->id;
            $settings = $this->showSetting($request->id);

            if ($request->type == 'destination') {
                $destinations = $settings->host;
                if (array_key_exists($request->host, $destinations)) {
                    $destinations[$request->host] = (int)$request->status;
                }

                $settings->host = $destinations;
                $settings->save();
            }
            if ($request->type == 'recipient-status') {
                $recipients = $settings->reciever;
                if (array_key_exists($request->reciever, $recipients)) {
                    $recipients[$request->reciever] = (int)$request->status;
                }
                $settings->reciever = $recipients;
                $settings->save();
            }
            if ($request->type == 'recipient') {
                $subjects = $settings->subject;
                if (array_key_exists($request->key, $subjects)) {
                    $subjects[$request->key] = $request->subject;
                }
                $templates = $settings->template;
                if (array_key_exists($request->key, $templates)) {
                    $templates[$request->key]['Email'] = $request->email_body;
                    $templates[$request->key]['SMS'] = $request->sms_body;
                    $templates[$request->key]['Web'] = $request->web_body;
                    $templates[$request->key]['App'] = $request->app_body;
                }
                $settings->subject = $subjects;
                $settings->template = $templates;
                $settings->save();

            }
           return true;
        } catch (\Exception $e) {
            Log::error('Notification Setting Update error');
            return false;
        }
    }


    public function readNotification($id){

        $notification = $this->show($id);
        $notification->is_read = 1;
        $notification->read_at = Carbon::now();
        $notification->save();
        return $notification;
    }

    /** Every notification ever sent to one person, newest first — for the
     *  admin-only "manage a person's notifications" screen. */
    public function userNotifications(int $userId)
    {
        return $this->model->where('reciver_id', $userId)->orderByDesc('id')->get();
    }

    public function deleteNotification(int $id): bool
    {
        return (bool) $this->model->where('id', $id)->delete();
    }

    public function deleteAllForUser(int $userId): bool
    {
        return (bool) $this->model->where('reciver_id', $userId)->delete();
    }

}
