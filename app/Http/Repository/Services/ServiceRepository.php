<?php

namespace App\Http\Repository\Services;

use App\Models\CompanyType;
use App\Models\Service;

class ServiceRepository {
    // Add services
    public static function AddService($request) {
        // Check Permission.
        $permission = ['Add/Edit Services'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        $service = Service::create($request->all());
        if($service) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Service created successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // Edit Services.
    public static function EditService($request, $id) {
        // Check Permission.
        $permission = ['Add/Edit Services'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        $service = Service::where('id',$id)->update($request->all());
        if($service) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Service update successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // All Services.
    public static function AllService($request, $id) {
        // Check Permission.
        $permission = ['Show Services'];
        if(PermissionCheck($permission) === true) {
            
        } else {
            return PermissionCheck($permission);
        }
        if($id != 0) {
            return ServiceRepository::ServicesById($id);
        } else {
            return ServiceRepository::ServiceListBySearch($request);
        }
    }

    // Service List with search filters.
    public static function ServiceListBySearch($request) {
        // with pagination.
        $services = Service::select("services.*");

        // 🔹 NEW: filter by company_type (safe & optional)
        if ($request->has('company_type') && !empty($request->company_type)) {
            $services->where('company_type', $request->company_type);
        }
        
        if($request->has('per_page') && !empty($request->per_page)) {
            if($request->has('search') && !empty($request->search)) {
                $services = $services->where('service', 'like', '%' . $request->search . '%');
            }
            $services = $services->paginate($request->per_page);
        } else {
        // Without Pagination.
        if($request->has('search') && !empty($request->search)) {
            $services = $services->where('service', 'like', '%' . $request->search . '%');
        }
        $services = $services->get();
        }
        if($services) {
            foreach($services as $service) {
                $service->company_type_name = CompanyType::where('id', $service->company_type)->pluck('name')->first();
            }
            return response()->json(['data' => $services, 'status' => 1, 'message' => "Service List!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 400);
        }
    }

    public static function ServicesById($id) {
        // with pagination.
        $services = Service::select("services.*")->where('id', $id)->first();
        if($services) {
            return response()->json(['data' => $services, 'status' => 1, 'message' => "Service List!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 400);
        }
    }
}

