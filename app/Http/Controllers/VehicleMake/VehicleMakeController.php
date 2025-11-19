<?php

namespace App\Http\Controllers\VehicleMake;

use App\Http\Repository\VehicleMake\VehicleMakeRepository;
use App\Http\Requests\VehicleMake\VehicleMakeRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleMakeController extends Controller
{
    // Create Make
    public function createMake(Request $request) {
        $validation = VehicleMakeRequest::VehicleMakeCreateValidation($request);
        if($validation === true) {
            return VehicleMakeRepository::AddMake($request);
        } else {
            return $validation;
        }
    }

    // Update Make
    public function updateMake(Request $request, $id) {
        $validation = VehicleMakeRequest::VehicleMakeCreateValidation($request);
        if($validation === true) {
            return VehicleMakeRepository::EditMake($request, $id);
        } else {
            return $validation;
        }
    }

    // Get make
    public function allMake(Request $request, $id = null) {
        return VehicleMakeRepository::GetMake($request, $id);
    }
    // Delete Make
    public function deleteMake($id) {
        return VehicleMakeRepository::RemoveMake($id);
    }
}
