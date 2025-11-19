<?php

namespace App\Http\Requests\VehicleModel;

use Illuminate\Foundation\Http\FormRequest;
use Validator;

class VehicleModelCreateRequest extends FormRequest
{
    public static function VehicleModelCreateValidation($request) {
        $validator = Validator::make($request->all(), [
            'make' => 'required',
            'model' => 'required|unique:vehicle_model',
        ]);

        if ($validator->fails()) {
            $errorMessage = '';
            // Loop through each field to find the first error
            foreach ($validator->errors()->messages() as $fieldErrors) {
                if (!empty($fieldErrors)) {
                    $errorMessage = $fieldErrors[0];
                    break;
                }
            }
            return response()->json(['data' => [], 'status' => 0, 'message' => $errorMessage], 422);
        } else {
            return true;
        }
    }
}
