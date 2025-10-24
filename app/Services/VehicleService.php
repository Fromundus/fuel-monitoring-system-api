<?php

namespace App\Services;

use App\Models\FuelDivisor;
use Illuminate\Support\Facades\DB;

class VehicleService
{
    public static function fetchVehicle(string $plate_no)
    {
        $vehicle = DB::connection("mysql2")->table("vehicles")->select("*")->where('plate_no', $plate_no)->latest('id')->first();

        $fuel_divisor = FuelDivisor::where('vehicle_id', $vehicle->id)->first();

        $vehicle->fuel_divisor = $fuel_divisor;

        return $vehicle;
    }

    public static function fetchVehicleById(int $id)
    {
        $vehicle = DB::connection("mysql2")->table("vehicles")->select("*")->where('id', $id)->latest('id')->first();

        $fuel_divisor = FuelDivisor::where('vehicle_id', $vehicle->id)->first();

        $vehicle->fuel_divisor = $fuel_divisor;

        return $vehicle;
    }
}