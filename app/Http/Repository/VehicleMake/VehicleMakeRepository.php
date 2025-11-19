<?php

namespace App\Http\Repository\VehicleMake;

use App\Models\VehicleMake;

class VehicleMakeRepository
{
    // Add Vehicle Make.
    public static function AddMake($request) {
        $make = VehicleMake::create($request->all());
        if($make) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Vehicle make created successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // Edit Make.
    public static function EditMake($request, $id) {
        $make = VehicleMake::where('id',$id)->update($request->all());
        if($make) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Vehicle make update successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // Get make. 
    public static function GetMake($request, $id = null) {
        $make = VehicleMakeRepository::FilterMake($request, $id);
        if($make) {
            return response()->json(['data' => $make, 'status' => 1, 'message' => "Vehicle make get successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // filter by make.
    public static function FilterMake($request, $id = null) {
        // Common query to get make data.
        $make = new VehicleMake();
        // Search by id.
        if(!empty($id)) {
            $make = $make->where('id',$id)->first();
            return $make;
        }
        // Search by name.
        if($request->has('search') && !empty($request->search)) {
            $make = $make->where('make','like','%'.$request->search.'%');
        }
        if($request->has('per_page') && !empty($request->per_page)) {
            $make = $make->orderBy('make','asc')->paginate($request->per_page);
        } else {
            $make = $make->orderBy('make','asc')->get();
        }
        return $make;
    }
    // Remove Make
    public static function RemoveMake($id) {
        $make = VehicleMake::where('id',$id)->delete();
        if($make) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Vehicle make Deleted successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }
}
