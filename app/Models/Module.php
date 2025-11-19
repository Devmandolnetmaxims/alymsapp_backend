<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Module extends Model
{
    use HasFactory;
    const MODULE_ESTIMATE = 1;
    const MODULE_JOB = 2;
    protected $guarded = [];
}
