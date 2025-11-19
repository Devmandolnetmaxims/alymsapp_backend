<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Validator;

class ResetPasswordRequest extends FormRequest
{
    public static function ResetPasswordValidation($request) {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
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
