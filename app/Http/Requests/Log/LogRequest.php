<?php

namespace App\Http\Requests\Log;

use Illuminate\Foundation\Http\FormRequest;
use Validation;

class LogRequest extends FormRequest
{
    public static function LogValidation($request) {
        $validator = Validator::make($request->all(), [
            'activity' => 'required',
            'action' => 'required',
            'comment' => 'required',
            'user_id' => 'required',
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
