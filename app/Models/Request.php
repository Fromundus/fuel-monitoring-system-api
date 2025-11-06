<?php

namespace App\Models;

use App\Http\Resources\EmployeeWithBalanceResource;
use App\Services\EmployeeService;
use App\Services\VehicleService;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    protected $appends = ['vehicle', 'amount'];

    protected $fillable = [
        "employeeid",
        "requested_by",

        "delegatedtoid",
        "delegated_to",

        "department",
        "division",

        'vehicle_id',

        'fuel_divisor',
            
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

        "billing_date",
        "unit_price",

        "type",

        "status",

        "source_id",

        "purpose_id",

        'date',

        "reference_number",

        'remarks',
    ];

    public function tripTickets(){
        return $this->hasMany(TripTicket::class);
    }

    public function logs(){
        return $this->hasMany(ActivityLog::class);
    }

    public function source(){
        return $this->belongsTo(Source::class);
    }

    public function requestPurpose(){
        return $this->belongsTo(Purpose::class, 'purpose_id');
    }

    public function getVehicleAttribute()
    {
        return $this->vehicle_id ? VehicleService::fetchVehicleById($this->vehicle_id) : null;
    }

    public function getAmountAttribute()
    {
        return number_format($this->quantity * $this->unit_price, 2);
    }
}
