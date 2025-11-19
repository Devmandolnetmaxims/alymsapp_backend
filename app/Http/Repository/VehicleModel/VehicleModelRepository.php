<?php

namespace App\Http\Repository\VehicleModel;

use App\Models\VehicleModel;
use App\Models\VehicleMake;

class VehicleModelRepository
{
    // Add Vehicle Model.
    public static function AddModel($request) {
        $model = VehicleModel::create($request->all());
        if($model) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Vehicle Model created successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // Edit Model.
    public static function EditModel($request, $id) {
        $model = VehicleModel::where('id',$id)->update($request->all());
        if($model) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Vehicle Model update successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }

    // Get Model.
    public static function GetModel($request, $id = null) {
        $model = VehicleModelRepository::FilterModel($request, $id);
        if($model) {
            return response()->json(['data' => $model, 'status' => 1, 'message' => "Vehicle Model get successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 400);
        }
    }

    // Filter model.
    public static function FilterModel($request, $id) {
        // Model common query.
        $model = VehicleMake::select('vehicle_model.*', 'vehicle_make.make as make_name')
                    ->join('vehicle_model', 'vehicle_model.make', '=', 'vehicle_make.id');
        // Search filter.
        if($request->has('search') && !empty($request->search)) {
            $model = $model->where(function($query) use ($request) {
                $query->where('vehicle_make.make', 'like', '%'.$request->search.'%')
                ->orWhere('vehicle_model.model', 'like', '%'.$request->search.'%');
            });
        }
        // Filter by make id.
        if($request->has('makeid') && !empty($request->makeid)) {
            $model = $model->where('vehicle_model.make', $request->makeid);
        }
        // Get details page.
        if($id) {
            $model = $model->where('vehicle_model.id', $id)->first();
            dd($model);
            return $model;
        }
        if($request->has('per_page') && !empty($request->per_page)) {
            $model = $model->paginate($request->per_page);
        } else {
            $model = $model->get();
        }
        return $model;
    }

    // Remove Model
    public static function RemoveModel($id) {
        $model = VehicleModel::where('id',$id)->delete();
        if($model) {
            return response()->json(['data' => [], 'status' => 1, 'message' => "Vehicle Model Deleted successfuly!!"], 200);
        } else {
            return response()->json(['data' => [], 'status' => 0, 'message' => "Something Wrong!!"], 500);
        }
    }
}
