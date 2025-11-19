<?php

namespace App\Http\Controllers\Estimate;

use App\Http\Requests\Estimate\EstimateCreateRequest;
use App\Http\Repository\Estimate\EstimateRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EstimateController extends Controller
{
    // Create Estimate.
    public function createEstimate(Request $request) {
        //Check the required Validation.
        $validation = EstimateCreateRequest::EstimateCreateValidation($request);
        if($validation === true) {
            //Create new Estimate.
            return EstimateRepository::AddEstimate($request);
        } else {
            return $validation;
        }
    }

    // Update Estimate.
    public function updateEstimate(Request $request, $id) {
        //Check the required Validation.
        $validation = EstimateCreateRequest::EstimateCreateValidation($request, $id);
        if($validation === true) {
            //Update new Estimate.
            return EstimateRepository::EditEstimate($request, $id);
        } else {
            return $validation;
        }
    }

    // Estimate list.
    public function allEstimate(Request $request, $id = null) {
        return EstimateRepository::AllList($request, $id);
    }

    // Delete estimate.
    public function deleteEstimate($id) {
        return EstimateRepository::RemoveEstimate($id);
    }

    // All estimate status.
    public function allStatus(Request $request, $id) {
        return EstimateRepository::AllStatus($request, $id);
    }
    
}
