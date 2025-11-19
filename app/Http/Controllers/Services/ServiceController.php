<?php

namespace App\Http\Controllers\Services;

use App\Http\Requests\Services\ServiceCreateRequest;
use App\Http\Repository\Services\ServiceRepository;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // Create services
    public function createService(Request $request)
    {
        $validation = ServiceCreateRequest::ServiceCreateValidation($request);
        if($validation === true) {
            return ServiceRepository::AddService($request);
        } else {
            return $validation;
        }
    }

    // Update services

    public function updateService(Request $request, $id)
    {
        $validation = ServiceCreateRequest::ServiceCreateValidation($request);
        if($validation === true) {
            return ServiceRepository::EditService($request, $id);
        } else {
            return $validation;
        }
    }

    // Service List.
    public function allService(Request $request, $id = 0) {
        return ServiceRepository::AllService($request, $id);
    }
}
