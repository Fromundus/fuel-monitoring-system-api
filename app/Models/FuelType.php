<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    protected $fillable = [
        "name",
        "unit",
        "unit_short",
    ];

    public function inventory(){
        return $this->hasOne(Inventory::class);
    }
}
