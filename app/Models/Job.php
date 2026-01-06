<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory, SoftDeletes;
    const JOB_ONSITE = 1;
    const JOB_INPROGRESS = 2;
    const JOB_DONE = 3;
    const JOB_COLLECT = 4;
    const ONSITE = "Onsite";
    const INPROGRESS = "In Progress";
    const DONE = "Compeleted";
    const COLLECT = "Collected";
    
    protected $guarded = [];
    
    public function estimateData()
    {
        return $this->belongsTo(Estimate::class, 'estimate_id', 'id');
    }
}
