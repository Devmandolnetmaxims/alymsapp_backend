<?php

namespace App\Http\Requests\Estimate;

use Illuminate\Foundation\Http\FormRequest;
use Validator;

class EstimateCreateRequest extends FormRequest
{
    public static function EstimateCreateValidation($request) {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'services' => 'required',
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
