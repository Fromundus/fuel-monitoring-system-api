<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedRouteGroup extends Model
{
    protected $fillable = [
        'fixed_route_id',
        'name'
    ];

    public function route(){
        return $this->belongsTo(FixedRoute::class);
    }

    public function rows(){
        return $this->hasMany(FixedRouteRow::class);
    }
}