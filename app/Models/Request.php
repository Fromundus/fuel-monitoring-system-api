<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $fillable = [
        "employeeid",
        "requested_by",

        "delegatedtoid",
        "delegated_to",

        "department",
        "division",
        "plate_number",
        "purpose",

        "quantity",
        "unit",
        "fuel_type_id",
        "fuel_type",

        "checked_by",
        "checked_by_date",
        "recommending_approval",
        "recommending_approval_date",
        "approved_by",
        "approved_by_date",
        "posted_by",
        "posted_by_date",

        "type",
        "source",

        "status",

        'date',

        'reference_number',
    ];

    public function tripTickets(){
        return $this->hasMany(TripTicket::class);
    }
}
