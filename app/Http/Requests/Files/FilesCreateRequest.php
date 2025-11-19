<?php

namespace App\Http\Requests\Files;

use Illuminate\Foundation\Http\FormRequest;
use Validator;

class FilesCreateRequest extends FormRequest
{
    public static function FilesCreateValidation($request) {
        $validator = Validator::make($request->all(), [
            'file.*' => 'required|mimes:jpeg,png,jpg,gif,svg',
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
