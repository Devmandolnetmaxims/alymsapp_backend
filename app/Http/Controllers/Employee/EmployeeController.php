<?php

namespace App\Http\Controllers\Employee;

use App\Http\Repository\Employee\EmployeeRepository;
use App\Http\Requests\Employee\CreateRequest;
use App\Http\Requests\Employee\UpdateRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class EmployeeController extends Controller
{
    // Create Employee.
    public function createEmployee(Request $request) {
        //Check the required Validation.
        $validation = CreateRequest::CreateValidation($request);
        if($validation === true) {
            //Create new Employee.
            return EmployeeRepository::AddEmployee($request);
        } else {
            return $validation;
        }
    }

    // Update Employee.
    public function updateEmployee(Request $request, $id) {
        try {
                //Check the required Validation.
                if($request->has('status') && ($request->status == User::INACTIVE_USER || $request->status == User::ACTIVE_USER)) {
                    $validation = true;
                } else {
                    $validation = UpdateRequest::UpdateValidation($request, $id);
                }
                if($validation === true) {
                    //Create new Employee.
                    return EmployeeRepository::EditEmployee($request, $id);
                } else {
                    return $validation;
            }
        } catch(\Exception $e) {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Contract Administrator!!"], 500);
        }
    }

    // All companies List.
    public function allEmployee(Request $request, $id = null) {
        try {
            return EmployeeRepository::AllList($request, $id);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Contract Administrator!!"], 500);
        }
    }

    //Delete Employee.
    public function deleteEmployee($id) {
        try {
            return EmployeeRepository::RemoveEmployee($id);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Contract Administrator!!"], 500);
        }
    }

    // Invitation re-send.
    public function invitationSend($id, Request $request) {
        try {
            return EmployeeRepository::SendInviteMail($id, $request);
        } catch (\Exception $e) {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Contract Administrator!!"], 500);
        }
    }
}
