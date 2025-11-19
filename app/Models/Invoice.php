<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];
    const STATUS_PAID = '1';
    const STATUS_UNPAID = '0';
    const STATUS_PARTIALPAID = '2';
    const STATUS_OVERDUE = '3';

    const BILL = '0';
    const INVOICE = '1';

    const CASH = '0';
    const CHEQUE = '1';
    const CREDIT_CARD = '2';
    const CASH_ADVANCE = '3';
    const ONLINE = '4';
}
