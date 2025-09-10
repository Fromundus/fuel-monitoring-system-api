<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barangay extends Model
{
    protected $fillable = [
        'name',
        'lat',
        'lng',
        'municipality',
    ];

    public function distancesA()
    {
        return $this->hasMany(BarangayDistance::class, 'barangay_a_id');
    }

    public function distancesB()
    {
        return $this->hasMany(BarangayDistance::class, 'barangay_b_id');
    }
}
