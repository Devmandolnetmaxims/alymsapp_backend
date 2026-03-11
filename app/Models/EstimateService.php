<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstimateService extends Model
{
    use HasFactory;
    protected $table = 'estimate_services';
    protected $fillable = ['service_id', 'estimate_id', 'temp_service', 'quantity', 'description', 'discount', 'rate', 'cost_rate', 'total'];
}
