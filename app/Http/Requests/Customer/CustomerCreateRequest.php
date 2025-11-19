<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Customer;
use Validator;

class CustomerCreateRequest extends FormRequest
{
    public static function CustomerCreateValidation($request) {
        $validator = Validator::make($request->all(), [
            // 'first_name' => 'required',
            // 'last_name' => 'required',
            'phone' => 'required|unique:customer|unique:users',
            'email' => 'nullable|email|unique:customer|unique:users',
            // 'trade_rate' => 'required_without:insurance_company,policy_number',
            // 'company_name' => 'required',
            // 'company_type' => 'required',
            // 'street' => 'required',
            // 'area' => 'required',
            // 'town' => 'required',
            // 'post_code' => 'required',
            // 'work_phone' => 'required',
            // 'insurance_company' => 'required_without:trade_rate',
            // 'policy_number' => 'required_without:trade_rate|unique:customer,policy_number,NULL,id,deleted_at,NULL',
            // 'policy_number' => [
            //     'required_without:trade_rate',
            //     Rule::unique('customer')->where('policy_number', '!=', null),
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
