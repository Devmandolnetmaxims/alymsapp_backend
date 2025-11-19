<?php

namespace App\Http\Repository\log;

use  App\Models\log;
class LogRepository
{
    // Add Log.
    public static function AddLog($request) {
        Log::create($request);
    }

    // Get Log.
    public static function GetLog($data = null) {
        $log = Log::select('logs.*');
        if(!empty($data['activity'])) {
            $log = $log->whereIn('activity', $data['activity']);
        }
        if(!empty($data['action'])) {
            $log = $log->where('action', $data['action']);
        }
        if(!empty($data['instance_id'])) {
            $log = $log->where('instance_id', $data['instance_id']);
        }
        $log = $log->orderBy('id', 'desc')->get();
        return $log;
    }
}