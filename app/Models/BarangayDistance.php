<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BarangayDistance extends Model
{
    protected $fillable = [
        'barangay_a_id',
        'barangay_b_id',
        'distance_meters',
        'time_ms',
        'route_raw'
    ];

    protected $casts = [
        'route_raw' => 'array'
    ];

    public function a()
    {
        return $this->belongsTo(Barangay::class, 'barangay_a_id');
    }

    public function b()
    {
        return $this->belongsTo(Barangay::class, 'barangay_b_id');
    }
}
