<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripTicket extends Model
{
    protected $fillable = [
        "request_id",
        "plate_number",
        "driver",
        'date',

        'milestone_value',
        'liters_per_milestone',
        'settings_snapshot_at',
    ];

    protected $casts = [
        'milestone_value' => 'decimal:2',
        'liters_per_milestone' => 'decimal:2',
        'settings_snapshot_at' => 'datetime',
    ];


    public function request(){
        return $this->belongsTo(Request::class);
    }

    public function rows(){
        return $this->hasMany(TripTicketRow::class);
    }
}
