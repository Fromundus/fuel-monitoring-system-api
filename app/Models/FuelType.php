<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelType extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "unit",
        "unit_short",
    ];

    public function inventory(){
        return $this->hasOne(Inventory::class);
    }
}
