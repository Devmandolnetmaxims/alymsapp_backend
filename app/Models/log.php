<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class log extends Model
{
    use HasFactory;
    const ESTIMATE = "Estimate";
    const JOB = "Job";
    const CUSTOMER = "Customer";
    const USER = "User";
    const SERVICE = "Service";
    const VEHICLE_MAKE = "VehicleMake";
    const VEHICLE_MODEL = "VehicleModel";
    const ACTION_CREATE = "Create";
    const ACTION_UPDATE = "Update";
    const ACTION_DELETE = "Delete";
    protected $guarded = [];
}
