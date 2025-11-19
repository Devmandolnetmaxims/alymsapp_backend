<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceHistory extends Model
{
    use HasFactory;
    protected $table = 'invoice_history';
    protected $guarded = [];

    const INVOICE_CREATE = 1;
    const INVOICE_UPDATE = 2;
    const INVOICE_DELETE = 3;
    const INVOICE_PAY = 4;
}
