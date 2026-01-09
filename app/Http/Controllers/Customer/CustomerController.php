<?php

namespace App\Http\Controllers\Customer;

use App\Http\Requests\Customer\CustomerCreateRequest;
use App\Http\Requests\Customer\CustomerUpdateRequest;
use App\Http\Repository\Customer\CustomerRepository;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    // Create Customer.
    public function createCustomer(Request $request) {
        //  Check Validation.
        $validation = CustomerCreateRequest::CustomerCreateValidation($request);
        if($validation === true) {
            // Customer Create logic.
            return CustomerRepository::AddCustomer($request);
        } else {
            return $validation;
        }
    }

    // Update Customer.
    public function updateCustomer(Request $request, $id) {
        // Check validation.
        $validation = CustomerUpdateRequest::CustomerUpdateValidation($request, $id);
        if($validation === true) {
            // Customer Create logic.
            return CustomerRepository::EditCustomer($request, $id);
        } else {
            return $validation;
        }
    }

    // Delete Customer.
    public function deleteCustomer($id) {
        return CustomerRepository::RemoveCustomer($id);
    }

    // All Customer List.
    public function allCustomer(Request $request, $id = null) {
        // try {
            return CustomerRepository::AllList($request, $id);
        // } catch (\Exception $e) {
        //     return response()->json(['data' => [], 'status' => 0, 'message' => "Contract Administrator!!"], 500);
        // }
    }

    // Customer Work History PDF.
    public function workHistoryPdf(Customer $customer) {
        return CustomerRepository::WorkHistoryPdf($customer);
    }
}
