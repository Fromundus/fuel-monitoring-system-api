<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AllowanceTransaction extends Model
{
    protected $fillable = [
        'employeeid',
        'type',
        'tx_type',
        'quantity',
        'reference',
        'granted_at',
    ];
}
