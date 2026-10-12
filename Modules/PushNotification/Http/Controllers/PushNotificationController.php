<?php

namespace Modules\PushNotification\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Modules\PushNotification\Services\PushNotificationService;

class PushNotificationController extends Controller
{

    // Store device token to the auth user
    public function storeFcmToken(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
        ]);

        $user = Auth::user();  // Get the authenticated user

        if ($user) {
            // Update the user's FCM token
            $user->device_token = $request->input('token');
            $user->save();

            return response()->json(['message' => 'Token saved successfully.']);
        }

        return response()->json(['error' => 'User not authenticated.'], 401);
    }


    // Subscribe the firebase channel/token
    public function subscribeToTopic(Request $request)
    {
        $token = $request->input('token');

        // Create channels
        $topic = auth()->user()->notification_subscribe_channel() ?? [];

        if (!$token || !$topic) {
            return response()->json(['status' => 'error', 'message' => 'Token and topic are required.'], 400);
        }

        try {
            $messaging = app('firebase.messaging');
            $messaging->subscribeToTopic($token, $topic);
            return response()->json(['status' => 'success', 'message' => 'Subscribed to topic.', 'channel' => $topic]);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }


    // Send a test notification using user token
    public function sendTestNotification(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'You must be logged in to send a test notification.'], 401);
        }

        $token = $user->device_token;

        if (!$token) {
            return response()->json(['status' => 'error', 'message' => 'No device token found for your account yet. Visit the site and allow notifications first.'], 400);
        }

        try {

            $message = CloudMessage::fromArray([
                'token' => $token,
                'notification' => [
                    'title' => 'Test Notification',
                    'body' => 'This is a test notification'
                ],
            ]);

            Firebase::messaging()->send($message);

            return response()->json(['status' => 'success', 'message' => 'Notification sent.']);
        } catch (\Throwable $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }

    }


    public function notification(Request $request)
    {
        try {
            $title = $request->input('title', 'Test Title');
            $body = $request->input('body', 'Notificaton body');
            (new PushNotificationService)->push($request->channel, $title, $body);
            $id = explode('user', $request->channel);
            (new PushNotificationService)->browserNotification($id[1], $title, $body);
            return response()->json(['message' => 'Notification send successfully']);
        } catch (\Exception $e) {
            return $e;
        }
    }


    public function generateServiceWorker(Request $request)
    {
        $firebaseConfig = [
            'apiKey' => env('FIREBASE_API_KEY'),
            'authDomain' => env('FIREBASE_AUTH_DOMAIN'),
            'projectId' => env('FIREBASE_PROJECT_ID'),
            'storageBucket' => env('FIREBASE_STORAGE_BUCKET'),
            'messagingSenderId' => env('FIREBASE_MESSAGING_SENDER_ID'),
            'appId' => env('FIREBASE_APP_ID'),
            'measurementId' => env('FIREBASE_MEASUREMENT_ID'),
        ];

        $serviceWorkerScript = <<<JS
            importScripts("https://www.gstatic.com/firebasejs/10.0.0/firebase-app-compat.js");
            importScripts("https://www.gstatic.com/firebasejs/10.0.0/firebase-messaging-compat.js");

            firebase.initializeApp({
                apiKey: "{$firebaseConfig['apiKey']}",
                authDomain: "{$firebaseConfig['authDomain']}",
                projectId: "{$firebaseConfig['projectId']}",
                storageBucket: "{$firebaseConfig['storageBucket']}",
                messagingSenderId: "{$firebaseConfig['messagingSenderId']}",
                appId: "{$firebaseConfig['appId']}",
                measurementId: "{$firebaseConfig['measurementId']}"
            });

            const messaging = firebase.messaging();

            messaging.onBackgroundMessage(function(payload) {
                console.log('Received background message ', payload);
                const notificationTitle = payload.notification.title;
                const notificationOptions = {
                    body: payload.notification.body,
                    icon: payload.notification.icon
                };

                self.registration.showNotification(notificationTitle, notificationOptions);
            });
        JS;

        return Response::make($serviceWorkerScript, 200)
            ->header('Content-Type', 'application/javascript');
    }

}
