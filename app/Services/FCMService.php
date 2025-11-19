<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class FCMService
{
    protected $client;
    protected $apiUrl;
    protected $credentialsPath;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiUrl = 'https://fcm.googleapis.com/v1/projects/alyms-14b72/messages:send';
        $this->credentialsPath = storage_path('app/alyms-14b72-firebase-adminsdk-mtcf9-541ae8944d.json');
    }

    public function sendNotification($title, $body, $token)
    {
        $message = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
            ],
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
            'Content-Type' => 'application/json',
        ];

        try {
            $response = $this->client->post($this->apiUrl, [
                'headers' => $headers,
                'json' => $message,
            ]);

            return json_decode($response->getBody(), true);
        } catch (\Exception $e) {
            Log::error('Failed to send FCM notification: ' . $e->getMessage());
            return false;
        }
    }

    protected function getAccessToken()
    {
        $client = new \Google_Client();
        $client->setAuthConfig($this->credentialsPath);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithAssertion();
        }

        return $client->getAccessToken()['access_token'];
    }
}
