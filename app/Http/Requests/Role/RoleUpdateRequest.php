<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Role;
use Validator;
use Illuminate\Validation\Rule;

class RoleUpdateRequest extends FormRequest
{
    public static function RoleUpdateValidation($request, $id) {
        $validator = Validator::make($request->all(), [
            'name' => [
                'required',
                Rule::unique('roles')->ignore($id),
            ],
            'label' => 'required'
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
