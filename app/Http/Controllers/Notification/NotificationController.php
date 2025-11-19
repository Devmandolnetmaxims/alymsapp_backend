<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Services\FCMService;
use App\Models\User;

class NotificationController extends Controller
{
    //
    protected $fcmService;
    public $title;
    public $body;

    public function __construct(FCMService $fcmService)
    {
        $this->fcmService = $fcmService;
    }

    public function sendAppNotification($title, $body, $id)
    {
        // get user details.
        $user = User::find($id);
        $token = $user->android_token ?? $user->ios_token;
        if(empty($token)) {
            return response()->json(['message' => 'Failed to send notification.'], 500);
        }
        $title = !empty($title) ? $title : 'Test Notification';
        $body = !empty($body) ? $body : 'This is a test push notification.';
        // $token = 'fM34Dw61TFay_29inXlD8L:APA91bEzLC7M00xjQe0f0dv_vpl4zIPDbdyT7Wovk4Tcxj_wGjZXUOBNJij9eZ3-mQ7T_yo1OxmuaMpVTFmXA3BnaA67QmlYczFXIA9H5ov--Z_ihT83OaZHEA0BXzRlzHH0BG0OqOEr'; // Replace with the device token

        $response = $this->fcmService->sendNotification($title, $body, $token);
    
        if ($response) {
            return response()->json(['message' => 'Notification sent successfully.']);
        } else {
            return response()->json(['message' => 'Failed to send notification.'], 500);
        }
    }
}
