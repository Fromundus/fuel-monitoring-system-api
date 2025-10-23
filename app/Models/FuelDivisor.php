<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelDivisor extends Model
{
    protected $fillable = [
        'vehicle_id',
        'km_divisor',
    ];
}
