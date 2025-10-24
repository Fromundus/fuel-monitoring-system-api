<?php

namespace App\Models;

use App\Http\Resources\EmployeeWithBalanceResource;
use App\Services\EmployeeService;
use App\Services\VehicleService;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $appends = ['vehicle'];

    protected $fillable = [
        "employeeid",
        "requested_by",

        "delegatedtoid",
        "delegated_to",

        "department",
        "division",
        "vehicle_id",
        "fuel_divisor",
        "purpose",

        "quantity",
        "unit",
        "fuel_type_id",
        "fuel_type",

        "approved_by",
        "approved_date",

        "released_by",
        "released_to",
        "released_date",

        "type",
        "source",

        "status",

        'date',

        'reference_number',

        'remarks'
    ];

    public function tripTickets(){
        return $this->hasMany(TripTicket::class);
    }

    public function logs(){
        return $this->hasMany(ActivityLog::class);
    }

    public function getVehicleAttribute()
    {
        return VehicleService::fetchVehicleById($this->vehicle_id);
    }
}
