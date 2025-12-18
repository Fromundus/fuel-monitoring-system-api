<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedRouteRow extends Model
{
    protected $fillable = [
        "fixed_route_group_id",
        "departure",
        "destination",
        "distance",
        // "quantity",
    ];

    public function group(){
        return $this->belongsTo(FixedRouteGroup::class);
    }
}