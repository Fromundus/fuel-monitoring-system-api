<?php

namespace App\Http\Controllers\Second;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index(Request $request){
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = DB::connection("mysql2")->table("vehicles")->select("*");

        if($search){
            $query->where(function($q) use ($search){
                $q->where("plate_no", 'like', "%{$search}%");
            });
        }


        $vehicles = $query->paginate($perPage);

        return response()->json([
            "vehicles" => $vehicles
        ]);
    }
}
