<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    const SUPER_ADMIN = 'super admin';
    const CHIEF = 'chief';
    const TECHNICIAN = 'technician';
    const IN_HOUSE = 'in house';
    const OUT_HOUSE = 'out house'; 
}
