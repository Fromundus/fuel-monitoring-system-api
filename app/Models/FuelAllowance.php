<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelAllowance extends Model
{
    protected $fillable = [
        "employeeid",
        'week_start',
        'allowance',
        'carried_over',
        'used',
        'advanced',
        'type',
    ];
}
