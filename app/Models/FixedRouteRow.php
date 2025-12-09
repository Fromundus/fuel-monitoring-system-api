<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedRouteRow extends Model
{
    protected $fillable = [
        "fixed_route_id",
        "departure",
        "destination",
        "distance",
        "quantity",
        'date',
    ];

    public function fixedRoute(){
        return $this->belongsTo(FixedRoute::class);
    }
}
