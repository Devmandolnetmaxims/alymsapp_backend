<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Validator;
use Auth;

class UpdateRequest extends FormRequest
{
    public static function UpdateValidation($request, $id) {
            $validator = Validator::make($request->all(), [
                'first_name' => 'required',
                'last_name' => 'required',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($id),
                ],
                'phone' => 'required',
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
