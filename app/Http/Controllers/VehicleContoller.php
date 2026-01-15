<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VehicleController extends Controller
{
    public function enquiry(Request $request)
    {
        print_r("hello");   die;
        // Validate input
        $request->validate([
            'registration_number' => 'required|string'
        ]);

        $url = 'https://driver-vehicle-licensing.api.gov.uk/vehicle-enquiry/v1/vehicles';
        $apiKey = env('DVLA_API_KEY'); // store key in .env

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'registrationNumber' => strtoupper($request->registration_number)
        ]);

        // Success response
        if ($response->successful()) {
            return response()->json([
                'success' => true,
                'data' => $response->json()
            ], 200);
        }

        // Error response
        return response()->json([
            'success' => false,
            'status' => $response->status(),
            'error' => $response->body()
        ], $response->status());
    }
}
