<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = "customer";
    protected $fillable = ['first_name',
                            'last_name',
                            'phone',
                            'email',
                            'trade_rate',
                            'company_name',
                            'company_type',
                            'street',
                            'area',
                            'town',
                            'post_code',
                            'work_phone',
                            'insurance_company',
                            'policy_number',
                            'due_date'
                        ];
}
