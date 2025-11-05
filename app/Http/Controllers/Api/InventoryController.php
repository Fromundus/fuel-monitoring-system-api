<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Warehousing\ItemResource;
use App\Models\FuelType;
use App\Models\Inventory;
use App\Models\Warehousing\Item;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function index(){
        $inventories = Item::where('InventoryTypeID', 5)->with('unit')->get();

        return response()->json([
            'inventories' => ItemResource::collection($inventories),
        ]);
    }
}
