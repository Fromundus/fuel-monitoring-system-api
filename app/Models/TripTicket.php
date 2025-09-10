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
    ];

    public function request(){
        return $this->belongsTo(Request::class);
    }

    public function rows(){
        return $this->hasMany(TripTicketRow::class);
    }
}
