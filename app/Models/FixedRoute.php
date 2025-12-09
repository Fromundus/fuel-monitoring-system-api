<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedRoute extends Model
{
    protected $fillable = [
        'name',
        'distance',
        'quantity',
    ];

    public function rows(){
        return $this->hasMany(FixedRouteRow::class);
    }
}
