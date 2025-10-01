<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\Request as ModelsRequest;
use App\Models\TripTicket;
use App\Models\TripTicketRow;
use App\Services\ActivityLogger;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RequestController extends Controller
{
    public function index(Request $request){
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $type = $request->query('type');
        $status = $request->query('status');

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

        if($status && $status !== 'all'){
            $query->where('status', $status);
        }

        $requests = $query->orderBy('id', 'desc')->paginate($perPage);

        $counts = [
            'total'      => ModelsRequest::count(),
            'allowance' => ModelsRequest::where('type', 'allowance')->count(),
            'delegated'      => ModelsRequest::where('type', 'delegated')->count(),
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
        $barangays = Barangay::all();
        
        return response()->json([
            "data" => $request,
            "barangays" => $barangays,
        ]);
    }
    
    public function store(Request $request){
        $type = $request->type;

        if($type === "trip-ticket"){
            if($request->fuel_type === "Gasoline" || $request->fuel_type === "Diesel"){
                $request->validate([
                    "employeeid" => "required|integer",
                    "requested_by" => "required|string",
                    "department" => "required|string",
                    "division" => "nullable|string",
                    "plate_number" => "nullable|string",
                    "purpose" => "required|string|max:255",
                    "quantity" => "required|numeric|min:0",
                    "unit" => "required|string",
                    "fuel_type_id" => "required|string",
                    "fuel_type" => "required|string",
                    "type" => "required|string",
                    "source" => "required|string",
        
                    "tripTickets"                  => "required|array|min:1",
                    "tripTickets.*.departure"      => "required|string",
                    "tripTickets.*.destination"    => "required|string",
                    "tripTickets.*.distance"       => "required|numeric|min:1",
                    "tripTickets.*.quantity"       => "required|numeric|min:0",
                    "tripTickets.*.date"           => "required|date|before_or_equal:today",
                ]);
            } else if ($request->fuel_type === "4T" || $request->fuel_type === "2T") {
                $request->validate([
                    "employeeid" => "required|integer",
                    "requested_by" => "required|string",
                    "department" => "required|string",
                    "division" => "nullable|string",
                    "plate_number" => "nullable|string",
                    "purpose" => "required|string|max:255",
                    "quantity" => "required|numeric|min:0",
                    "unit" => "required|string",
                    "fuel_type_id" => "required|string",
                    "fuel_type" => "required|string",
                    "type" => "required|string",
                    "source" => "required|string",
                ]);
            }
        } else if ($type === "allowance") {
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                "source" => "required|string",
            ]);

            $currentBalance = EmployeeService::getCurrentBalance($request->employeeid, $this->getAllowanceType($request->fuel_type));

            if($request->quantity > $currentBalance){
                return response()->json([
                    "message" => "Grabe ka na"
                ], 422);
            }
        } else if ($type === "delegated"){
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "delegatedtoid" => "required|integer",
                "delegated_to" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                "source" => "required|string",
            ]);
        } else if ($type === "emergency"){
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                "source" => "required|string",
            ]);
        }

        try {

            DB::beginTransaction();

            $reference_number = 'REF-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));

            $fuelRequest = ModelsRequest::create([
                "employeeid" => $request->employeeid,
                "requested_by" => $request->requested_by,

                "delegatedtoid" => $type === "delegated" ? $request->delegatedtoid : null,
                "delegated_to" => $type === "delegated" ? $request->delegated_to : null,

                "department" => $request->department,

                "division" => $request->division ?? null,

                "plate_number" => $request->plate_number ?? null,
                "purpose" => $request->purpose,
                "quantity" => $request->quantity,
                "unit" => $request->unit,
                "fuel_type_id" => $request->fuel_type_id,
                "fuel_type" => $request->fuel_type,
                "type" => $request->type,

                "source" => $request->source,

                "date" => Carbon::now(),

                "reference_number" => $reference_number,
            ]);


            if($fuelRequest && $type === "trip-ticket"){
                $tripTicket = TripTicket::create([
                    "request_id" => $fuelRequest->id,
                    "plate_number" => $fuelRequest->plate_number ?? null,
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

            ActivityLogger::log([
                'action' => 'created',
                'request' => $fuelRequest
            ]);
            
            DB::commit();
            
            return response()->json([
                "data" => $fuelRequest,
                "message" => "Request Successfully Created", 
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

    }

    public function updateStatus(Request $request, $id){
        $validated = $request->validate([
            "status" => "required|string|in:pending,approved,rejected,released,cancelled"
        ]);

        try {
            DB::beginTransaction();

            $fuelRequest = ModelsRequest::findOrFail($id);
    
            if($fuelRequest){
                $fuelRequest->update([
                    "status" => $validated["status"],
                ]);
    
                if($validated["status"] === "released" && ($fuelRequest->type === "allowance" || $fuelRequest->type === "delegated")){
                    $allowance = EmployeeService::getLatestBalance($fuelRequest->employeeid, $this->getAllowanceType($fuelRequest->fuel_type));
    
                    $allowance->update([
                        "used" => $allowance->used + $fuelRequest->quantity,
                    ]);
                }
    
                if ($validated["status"] === "released" && $fuelRequest->type === "trip-ticket" && $this->getAllowanceType($fuelRequest->fuel_type) === "gasoline-diesel") {
                    EmployeeService::checkTripTicketAllowance($fuelRequest->employeeid);
                } else if ($validated["status"] === "released" && $fuelRequest->type === "trip-ticket" && $this->getAllowanceType($fuelRequest->fuel_type) === "4t2t") {
                    $allowance = EmployeeService::getLatestBalance($fuelRequest->employeeid, 'trip-ticket-allowance');
    
                    $allowance->update([
                        "used" => $allowance->used + $fuelRequest->quantity,
                    ]);
                }

                ActivityLogger::log([
                    'action' => $validated['status'],
                    'request' => $fuelRequest
                ]);

                DB::commit();
    
                return response()->json([
                    "message" => "Updated Successfully"
                ]);
            }

        } catch (\Exception $e){
            DB::rollBack();

            throw $e;
        }

    }

    public function scanRequest(Request $request){
        $validated = $request->validate([
            "reference_number" => "required|string",
        ]);

        $fuelRequest = ModelsRequest::where("reference_number", $validated["reference_number"])->first();

        if($fuelRequest){
            return response()->json([
                "data" => $fuelRequest->id,
            ]);
        } else {
            return response()->json([
                "message" => "Request not Found",
            ], 404);
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

    private function getAllowanceType(string $type){
        $fuelType = '';

        if($type === "Diesel" || $type === "Gasoline"){
            $fuelType = 'gasoline-diesel';
        } else if ($type === "4T" || $type === "2T"){
            $fuelType = '4t2t';
        } else if ($type === "B-fluid"){
            $fuelType = 'bfluid';
        }

        return $fuelType;
    }
}
