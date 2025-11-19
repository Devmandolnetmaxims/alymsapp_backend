<?php

namespace App\Http\Requests\Services;

use Illuminate\Foundation\Http\FormRequest;
use Validator;

class ServiceCreateRequest extends FormRequest
{
    public static function ServiceCreateValidation($request) {
        $validator = Validator::make($request->all(), [
            'company_type' => 'required',
            'service' => 'required',
            'rate' => 'required',
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
