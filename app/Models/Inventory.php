<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        "fuel_type_id",
        "quantity",
    ];

    public function fuelType(){
        return $this->belongsTo(FuelType::class);
    }
}
