<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $fillable = [
        "fuel_type_id",
        "quantity",
    ];

    public function fuelType(){
        return $this->belongsTo(FuelType::class);
    }
}
