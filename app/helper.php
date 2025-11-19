<?php

use App\Http\Repository\log\LogRepository;
use App\Http\Repository\Comment\CommentRepository;
use Illuminate\Support\Facades\Auth;
use Twilio\Rest\Client;


if(!function_exists('get_log')) {
    function get_log($request) {
        LogRepository::AddLog($request);
    }
}

if(!function_exists('get_comment')) {
    function get_comment($request) {
        LogRepository::AddComment($request);
    }
}

if(!function_exists('PermissionCheck')) {
    function PermissionCheck($permissions) {
        $roles = Auth::user()->getRoleNames();
        if(Auth::user()->getRoleNames()[0] != "super admin") {
            if(!Auth::user()->hasAllPermissions($permissions)) {
                return response()->json(['data' => [], 'status' => 0, 'message' => 'Unauthorized Access'], 401);
            } else {
                return true;
            }
        } else {
            return true;
        }
    }
}

if (!function_exists('sendTwilioSms')) {
    function sendTwilioSms($to, $message)
    {
        $sid = env('TWILIO_SID');
        $token = env('TWILIO_AUTH_TOKEN');
        $twilioNumber = env('TWILIO_PHONE_NUMBER');

        try {
            $twilio = new Client($sid, $token);
            $twilio->messages->create($to, [
                'from' => $twilioNumber,
                'body' => $message
            ]);
            return ['status' => 'success', 'message' => 'SMS sent successfully.'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}