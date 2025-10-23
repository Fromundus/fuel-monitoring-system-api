<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class VehicleService
{
    public static function fetchVehicle(string $plate_no)
    {
        $vehicle = DB::connection("mysql2")->table("vehicles")->select("*")->where('plate_no', $plate_no)->latest('id')->first();

        return $vehicle;
    }
}