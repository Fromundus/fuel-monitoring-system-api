<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelType;
use App\Models\Inventory;
use Illuminate\Http\Request;

class FuelTypeController extends Controller
{
    public function index()
    {
        $fuelTypes = FuelType::with('inventory')->get();
        return response()->json($fuelTypes);
    }

    /**
     * Store a newly created fuel type in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:fuel_types,name',
            'unit' => 'required|nullable|string',
            'unit_short' => 'required|nullable|string'
        ]);

        $fuelType = FuelType::create([
            'name' => $request->name,
            'unit' => $request->unit ?? 'Liters',
            'unit_short' => $request->unit_short ?? 'L',
        ]);

        if($fuelType){
            Inventory::create([
                'fuel_type_id' => $fuelType->id,
                'quantity' => 0,
            ]);
        }

        return response()->json([
            'message' => 'Fuel type created successfully.',
            'data' => $fuelType
        ], 201);
    }

    /**
     * Display the specified fuel type.
     */
    public function show($id)
    {
        $fuelType = FuelType::findOrFail($id);
        return response()->json($fuelType);
    }

    /**
     * Update the specified fuel type in storage.
     */
    public function update(Request $request, $id)
    {
        $fuelType = FuelType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|unique:fuel_types,name,' . $fuelType->id,
            'unit' => 'required|nullable|string',
            'unit_short' => 'required|nullable|string'
        ]);

        $fuelType->update([
            'name' => $request->name,
            'unit' => $request->unit ?? $fuelType->unit,
            'unit_short' => $request->unit_short ?? $fuelType->unit_short,
        ]);

        return response()->json([
            'message' => 'Fuel type updated successfully.',
            'data' => $fuelType
        ]);
    }

    /**
     * Remove the specified fuel type from storage.
     */
    public function destroy($id)
    {
        $fuelType = FuelType::findOrFail($id);
        $fuelType->delete();

        return response()->json([
            'message' => 'Fuel type deleted successfully.'
        ]);
    }
}
