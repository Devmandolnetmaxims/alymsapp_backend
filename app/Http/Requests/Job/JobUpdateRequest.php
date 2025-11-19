<?php

namespace App\Http\Requests\Job;

use Illuminate\Foundation\Http\FormRequest;
use Validator;

class JobUpdateRequest extends FormRequest
{
    public static function JobUpdateValidation($request) {
        $validator = Validator::make($request->all(), [
            // 'user_id' => 'required_without:team',
            // 'services' => 'required_without:team',
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
