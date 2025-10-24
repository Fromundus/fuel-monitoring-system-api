<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelDivisor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FuelDivisorController extends Controller
{
    public function update(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'fuel_divisor' => 'required|numeric|min:1',
        ]);

        foreach ($validated['ids'] as $vehicleId) {
            FuelDivisor::updateOrCreate(
                ['vehicle_id' => $vehicleId],
                ['km_divisor' => $validated['fuel_divisor']]
            );
        }

        return response()->json(['message' => 'Fuel divisors created/updated successfully']);
    }
}
