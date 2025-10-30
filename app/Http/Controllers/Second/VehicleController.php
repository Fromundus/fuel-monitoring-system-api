<?php

namespace App\Http\Controllers\Second;

use App\Http\Controllers\Controller;
use App\Models\FuelDivisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    // public function index(Request $request){
    //     $search  = $request->query('search');
    //     $perPage = $request->query('per_page', 10);

    //     $brand = $request->query('brand');
    //     $model = $request->query('model');

    //     // $query = DB::connection("mysql2")->table("vehicles")->select("*");

    //     $query = DB::connection('mysql2')
    //         ->table('vehicles as v')
    //         ->select('v.*')
    //         ->join(
    //             DB::raw('(SELECT plate_no, MAX(id) as latest_id FROM vehicles GROUP BY plate_no) as latest'),
    //             function ($join) {
    //                 $join->on('v.id', '=', 'latest.latest_id');
    //             }
    //         );

    //     if($search){
    //         $query->where(function($q) use ($search){
    //             $q->where("plate_no", 'like', "%{$search}%")
    //             // ->orWhere("make", 'like', "%{$search}%")
    //             ->orWhere("reg_owner", 'like', "%{$search}%");
    //         });
    //     }

    //     if($brand && $brand !== ""){
    //         $query->where('make', $brand);
    //     }

    //     if($model && $model !== ""){
    //         $query->where('model', $model);
    //     }

    //     $vehicles = $query->paginate($perPage);

    //     // Get all vehicle IDs in this page
    //     $vehicleIds = collect($vehicles->items())->pluck('id');

    //     // Fetch related fuel divisors from the default connection
    //     $fuelDivisors = DB::connection('mysql')
    //         ->table('fuel_divisors')
    //         ->whereIn('vehicle_id', $vehicleIds)
    //         ->get()
    //         ->keyBy('vehicle_id');

    //     // Attach fuel divisor data to each vehicle
    //     $vehicles->getCollection()->transform(function ($vehicle) use ($fuelDivisors) {
    //         $vehicle->fuel_divisor = $fuelDivisors->get($vehicle->id);
    //         return $vehicle;
    //     });


    //     return response()->json([
    //         "vehicles" => $vehicles
    //     ]);
    // }

    public function index(Request $request)
    {
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $brand = $request->query('brand');
        $model = $request->query('model');

        $query = DB::connection('mysql2')
            ->table('vehicles as v')
            ->select('v.*')
            ->join(
                DB::raw('(SELECT plate_no, MAX(id) as latest_id FROM vehicles GROUP BY plate_no) as latest'),
                function ($join) {
                    $join->on('v.id', '=', 'latest.latest_id');
                }
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where("v.plate_no", 'like', "%{$search}%")
                ->orWhere("v.reg_owner", 'like', "%{$search}%");
            });
        }

        if ($brand && $brand !== "") {
            $query->where('v.make', $brand);
        }

        if ($model && $model !== "") {
            $query->where('v.model', $model);
        }

        $vehicles = $query->paginate($perPage);

        // Get all vehicle IDs in this page
        $vehicleIds = collect($vehicles->items())->pluck('id');

        // Fetch related fuel divisors from the default connection
        $fuelDivisors = DB::connection('mysql')
            ->table('fuel_divisors')
            ->whereIn('vehicle_id', $vehicleIds)
            ->get()
            ->keyBy('vehicle_id');

        // Attach fuel divisor data to each vehicle
        $vehicles->getCollection()->transform(function ($vehicle) use ($fuelDivisors) {
            $vehicle->fuel_divisor = $fuelDivisors->get($vehicle->id);
            return $vehicle;
        });

        return response()->json([
            "vehicles" => $vehicles
        ]);
    }

    public function show($plate_number){
        $vehicle = DB::connection('mysql2')
            ->table('vehicles as v')
            ->select('v.*')
            ->join(
                DB::raw('(SELECT plate_no, MAX(id) as latest_id FROM vehicles GROUP BY plate_no) as latest'),
                function ($join) {
                    $join->on('v.id', '=', 'latest.latest_id');
                }
            )
            ->where('v.plate_no', $plate_number)
            ->first();

        // Fetch related fuel divisors from the default connection
        if($vehicle){
            $fuelDivisor = DB::connection('mysql')
                ->table('fuel_divisors')
                ->where('vehicle_id', $vehicle->id)
                ->first();
    
            $vehicle->fuel_divisor = $fuelDivisor;
            return response()->json([
                "data" => $vehicle
            ]);
        } else {
            return response()->json([
                "message" => "Vehicle not found."
            ]);
        }
    }

}
