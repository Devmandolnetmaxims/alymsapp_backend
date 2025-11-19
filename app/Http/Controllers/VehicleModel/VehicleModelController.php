<?php

namespace App\Http\Controllers\VehicleModel;

use App\Http\Repository\VehicleModel\VehicleModelRepository;
use App\Http\Requests\VehicleModel\VehicleModelCreateRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class VehicleModelController extends Controller
{
       // Create Model
       public function createModel(Request $request) {
        $validation = VehicleModelCreateRequest::VehicleModelCreateValidation($request);
        if($validation === true) {
            return VehicleModelRepository::AddModel($request);
        } else {
            return $validation;
        }
    }

    // Update Model
    public function updateModel(Request $request, $id) {
        $validation = VehicleModelCreateRequest::VehicleModelCreateValidation($request);
        if($validation === true) {
            return VehicleModelRepository::EditModel($request, $id);
        } else {
            return $validation;
        }
    }

    // Get model.
    public function allModel(Request $request,$id = null) {
        return VehicleModelRepository::GetModel($request, $id);
    }

    // Delete Model
    public function deleteModel($id) {
        return VehicleModelRepository::RemoveModel($id);
    }
}
