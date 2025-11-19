<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MotController extends Controller
{
    /**
     * Get Access Token from Microsoft Entra ID (Azure AD)
     */
    private function getAccessToken()
    {
        $response = Http::asForm()->post(env('MOT_TOKEN_URL'), [
            'grant_type' => 'client_credentials',
            'client_id' => env('MOT_CLIENT_ID'),
            'client_secret' => env('MOT_CLIENT_SECRET'),
            'scope' => env('MOT_SCOPE'),
        ]);

        if (!$response->successful()) {
            return response()->json([
                'error' => 'Failed to obtain token',
                'details' => $response->json(),
            ], $response->status());
        }

        return $response->json()['access_token'];
    }

    /**
     * Fetch MOT history by vehicle registration number
     */
    public function fetchMotData($registration)
    {
        // Step 1: Get token
        $token = $this->getAccessToken();
        if (is_object($token)) {
            // Means an error JSON was returned
            return $token;
        }

        // Step 2: Make API request
        $url = "https://history.mot.api.gov.uk/v1/trade/vehicles/registration/" . strtoupper($registration);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-API-Key' => env('MOT_API_KEY'),
            'Accept' => 'application/json+v6',
        ])->get($url);

        // Step 3: Handle responses
        if ($response->status() === 200) {
            return response()->json($response->json(), 200);
        } elseif ($response->status() === 404) {
            return response()->json(['message' => "No MOT records found for {$registration}."], 404);
        } elseif ($response->status() === 403) {
            return response()->json(['message' => 'Forbidden – check your API key or token permissions.'], 403);
        }

        return response()->json([
            'error' => 'Unknown error',
            'status' => $response->status(),
            'details' => $response->body(),
        ], $response->status());
    }
}
