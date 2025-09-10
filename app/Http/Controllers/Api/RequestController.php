<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as ModelsRequest;
use App\Models\TripTicket;
use App\Models\TripTicketRow;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RequestController extends Controller
{
    public function index(Request $request){
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $type = $request->query('type');

        $query = ModelsRequest::query()->with("tripTickets.rows");

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('requested_by', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%")
                ->orWhere('plate_number', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        $requests = $query->orderBy('id', 'desc')->paginate($perPage);

        $counts = [
            'total'      => ModelsRequest::count(),
            'allowance' => ModelsRequest::where('type', 'allowance')->count(),
            'trip_ticket'      => ModelsRequest::where('type', 'trip-ticket')->count(),
            'emergency'       => ModelsRequest::where('type', 'emergency')->count(),
        ];

        return response()->json([
            "requests" => $requests,
            "counts" => $counts,
        ]);
    }

    public function show($id){
        $request = ModelsRequest::with("tripTickets.rows")->findOrFail($id);
        
        return response()->json([
            "data" => $request,
        ]);
    }

    public function store(Request $request){
        $request->validate([
            "employeeid" => "required|integer",
            "requested_by" => "required|string",
            "department" => "required|string",
            "plate_number" => "required|string",
            "purpose" => "required|string",
            "quantity" => "required|numeric|min:1",
            "unit" => "required|string",
            "fuel_type_id" => "required|string",
            "fuel_type" => "required|string",
            "type" => "required|string",

            "tripTickets"                  => "required|array|min:1",
            "tripTickets.*.departure"      => "required|string",
            "tripTickets.*.destination"    => "required|string",
            "tripTickets.*.distance"       => "required|numeric|min:1",
            "tripTickets.*.quantity"       => "required|numeric|min:1",
            "tripTickets.*.date"           => "required|date|before_or_equal:today",
        ]);

        try {

            DB::beginTransaction();

            $fuelRequest = ModelsRequest::create([
                "employeeid" => $request->employeeid,
                "requested_by" => $request->requested_by,
                "department" => $request->department,
                "plate_number" => $request->plate_number,
                "purpose" => $request->purpose,
                "quantity" => $request->quantity,
                "unit" => $request->unit,
                "fuel_type_id" => $request->fuel_type_id,
                "fuel_type" => $request->fuel_type,
                "type" => $request->type,

                "date" => Carbon::now(),
            ]);

            if($fuelRequest){
                $tripTicket = TripTicket::create([
                    "request_id" => $fuelRequest->id,
                    "plate_number" => $fuelRequest->plate_number,
                    "driver" => $fuelRequest->requested_by,
                    "date" => $fuelRequest->date,
                ]);

                if($tripTicket){
                    $tripTicketRows = $request->tripTickets;

                    foreach($tripTicketRows as $item){
                        TripTicketRow::create([
                            "trip_ticket_id" => $tripTicket->id,
                            "departure" => $item["departure"],
                            "destination" => $item["destination"],
                            "distance" => $item["distance"],
                            "quantity" => $item["quantity"],
                            "date" => $item["date"],
                        ]);
                    }
                    
                }
            }
            
            DB::commit();
            
            return response()->json([
                "message" => "Request Successfully Created", 
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

    }

    public function delete(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        $requests = ModelsRequest::whereIn('id', $validated['ids'])->get();

        ModelsRequest::whereIn('id', $validated['ids'])->delete();

        // foreach($users as $user){
        //     ActivityLogger::log('delete', 'account', "Deleted account: #" . $user->id . " " . $user->name);
        // }

        return response()->json(['message' => 'Requests deleted successfully']);
    }
}
