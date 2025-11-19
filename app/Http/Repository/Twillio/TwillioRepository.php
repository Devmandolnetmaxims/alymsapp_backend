<?php

namespace App\Http\Repository\Twillio;

use App\Services\TwilioService;

class TwillioRepository
{
    protected $twilio;

    public function __construct(TwilioService $twilio)
    {
        $this->twilio = $twilio;
    }

    public function sendSms($request)
    {
        $request->validate([
            'to' => 'required|string',
            'message' => 'required|string',
        ]);

        $to = $request->input('to');
        $message = $request->input('message');

        $response = $this->twilio->sendSms($to, $message);

        return response()->json($response);
    }
}