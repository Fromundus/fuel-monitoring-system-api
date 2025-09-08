<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripTicketRow extends Model
{
    protected $fillable = [
        "trip_ticket_id",
        "departure",
        "destination",
        "distance",
        "quantity",
        'date',
    ];

    public function tripTicket(){
        return $this->belongsTo(TripTicket::class);
    }
}
