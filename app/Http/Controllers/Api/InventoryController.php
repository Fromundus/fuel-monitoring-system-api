<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelType;
use App\Models\Inventory;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);

        $query = Inventory::query()->with("fuelType");
        $fuelTypes = FuelType::all();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('fuelType', function($q2) use ($search) {
                    $q2->where('name', 'like', "%{$search}%");
                });
            });
        }

        $inventories = $query->orderBy('id', 'desc')->paginate($perPage);

        return response()->json([
            'inventories' => $inventories,
            "fuelTypes" => $fuelTypes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'fuel_type_id' => 'required|exists:fuel_types,id',
            'quantity'     => 'required|numeric|min:0.01'
        ]);

        $inventory = Inventory::where('fuel_type_id', $request->fuel_type_id)->first();

        if ($inventory) {
            // Update existing record by adding to quantity
            $inventory->quantity += $request->quantity;
            $inventory->save();

            $message = 'Inventory updated successfully (quantity added).';
        } else {
            // Create a new inventory record
            $inventory = Inventory::create([
                'fuel_type_id' => $request->fuel_type_id,
                'quantity'     => $request->quantity,
            ]);

            $message = 'Inventory record created successfully.';
        }

        return response()->json([
            'message' => $message,
            'data'    => $inventory->load('fuelType')
        ], 201);
    }

    /**
     * Display a specific inventory item.
     */
    public function show($id)
    {
        $inventory = Inventory::with('fuelType')->findOrFail($id);
        return response()->json($inventory);
    }

    /**
     * Update the stock quantity manually.
     * Normally use TransactionsController for restock/consumption.
     */
    public function update(Request $request, $id)
    {
        $inventory = Inventory::findOrFail($id);

        $request->validate([
            'quantity' => 'required|numeric|min:0'
        ]);

        $inventory->update([
            'quantity' => $request->quantity
        ]);

        return response()->json([
            'message' => 'Inventory updated successfully.',
            'data' => $inventory->load('fuelType')
        ]);
    }

    public function destroy(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        Inventory::whereIn('fuel_type_id', $validated['ids'])->get();

        Inventory::whereIn('fuel_type_id', $validated['ids'])->delete();

        FuelType::whereIn('id', $validated['ids'])->delete();

        // foreach($users as $user){
        //     ActivityLogger::log('delete', 'account', "Deleted account: #" . $user->id . " " . $user->name);
        // }

        return response()->json(['message' => 'Users deleted successfully']);
    }
}
