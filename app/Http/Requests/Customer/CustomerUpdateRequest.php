<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Validator;

class CustomerUpdateRequest extends FormRequest
{
    public static function CustomerUpdateValidation($request, $id) {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            // 'last_name' => 'required',
            'phone' => [
                'required',
                Rule::unique('customer')->ignore($id),
                'unique:users',
            ],
            'email' => 'nullable|email|unique:customer|unique:users',
            'email' => [
                'nullable',
                'email',
                Rule::unique('customer')->ignore($id),
                'unique:users',
            ],
            // 'trade_rate' => 'required_without:insurance_company,policy_number',
            // 'company_name' => 'required',
            // 'company_type' => 'required',
            // 'street' => 'required',
            // 'area' => 'required',
            // 'town' => 'required',
            // 'post_code' => 'required',
            // 'work_phone' => 'required',
            // 'policy_number' => [
            //     'required_without:trade_rate',
            //     Rule::unique('customer')->ignore($id)->where(function ($query) use ($request) {
            //         return $query->whereNotNull('policy_number');
            //     }),
            // ],
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
