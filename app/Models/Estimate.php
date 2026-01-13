<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estimate extends Model
{
    use HasFactory, SoftDeletes;

    const APPROVED = "Approved";
    const PENDING = "Pending";
    const DRAFT = "Draft";
    const REVIEW = "Review";

    protected $table = "estimates";
    protected $fillable = ['user_id', 'registration', 'make_id', 'model_id', 'paint_code', 'description', 'files', 'status', 'created_by', 'draft','net_total', 'net_discount', 'net_vat', 'ref_no', 'grand_total', 'amount', 'approved_on', 'module', 'approved_by', 'first_used_date', 'fuel_type', 'registration_date', 'manufacture_date', 'engine_size'];
    // protected $guarded = [];

    public function services()
    {
        return $this->hasMany(EstimateService::class);
    }
}
