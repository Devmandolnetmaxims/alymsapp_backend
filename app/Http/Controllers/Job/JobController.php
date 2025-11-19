<?php

namespace App\Http\Controllers\Job;

use App\Http\Requests\Estimate\EstimateCreateRequest;
use App\Http\Requests\Job\JobUpdateRequest;
use App\Http\Repository\Job\JobRepository;
use App\Http\Controllers\Controller;
use App\Http\Repository\Job\AddJob;
use Illuminate\Http\Request;

class JobController extends Controller
{
    // Create Job.
    public function createJob(Request $request) {
        //Check the required Validation.
        $validation = EstimateCreateRequest::EstimateCreateValidation($request);
        if($validation === true) {
            //Create new Estimate.
            return JobRepository::AddJob($request);
        } else {
            return $validation;
        }
    }

    // Update Job.
    public function updateJob(Request $request, $id) {
        //Check the required Validation.
        $validation = JobUpdateRequest::JobUpdateValidation($request, $id);
        if($validation === true) {
            //Update new Estimate.
            return JobRepository::EditJob($request, $id);
        } else {
            return $validation;
        }
    }

    // Job list.
    public function allJob(Request $request, $id = null){
        return JobRepository::JobAllList($request, $id);
    }

    //  Estimate conver to job.
    public function jobConvert(Request $request) {
        return JobRepository::EstimateToJob($request);
    }

    // Assign job to team.
    public function assignTeam(Request $request, $id) {
        return JobRepository::AssignTeam($request, $id);
    }

    // Delete jobs.
    public function deleteJob($id) {
        return JobRepository::RemoveJob($id);
    }
}
