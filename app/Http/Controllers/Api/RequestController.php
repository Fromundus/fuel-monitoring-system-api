<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeWithBalanceResource;
use App\Models\Barangay;
use App\Models\Request as ModelsRequest;
use App\Models\Source;
use App\Models\TripTicket;
use App\Models\TripTicketRow;
use App\Models\Warehousing\Item;
use App\Models\Warehousing\TransactionWarehousing;
use App\Services\ActivityLogger;
use App\Services\AllowanceService;
use App\Services\BalanceWarehouseService;
use App\Services\EmployeeService;
use App\Services\MilestoneAllowanceService;
use App\Services\RequestService;
use App\Services\VehicleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RequestController extends Controller
{
    public function index(Request $request){
        $search = $request->query('search');
        $perPage = $request->query('per_page', 10);
        $type = $request->query('type');
        $status = $request->query('status');
        $fuel_type = $request->query('fuel_type');
        $source = $request->query('source');

        $billing_date = $request->query('billing_date');

        $query = ModelsRequest::query()->with(["tripTickets.rows", "logs", "source"]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('requested_by', 'like', "%{$search}%")
                ->orWhere('department', 'like', "%{$search}%")
                ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        if ($type && $type !== 'all') {
            $query->where('type', $type);
        }

        if($status && $status !== 'all'){
            $query->where('status', $status);
        }

        if($fuel_type && $fuel_type !== 'all'){
            $query->where('fuel_type', $fuel_type);
        }

        if($source){
            if($source === "outside"){
                $query->whereNot('source_id', 1);
            } else if ($source !== 'all' && $source !== "outside"){
                $query->where('source_id', $source);
            }
        }

        if($billing_date && $billing_date !== 'all'){
            $query->where('billing_date', $billing_date);
        }

        $grandTotal = (clone $query)
            ->select(DB::raw('SUM(quantity * unit_price) as total'))
            ->value('total') ?? 0;

        if ($perPage === 'all' || (int)$perPage === 0) {
            $allRequests = $query->orderBy('updated_at', 'desc')->get();

            $requests = [
                "current_page" => 1,
                "data" => $allRequests,
                "from" => 1,
                "last_page" => 1,
                "per_page" => $allRequests->count(),
                "to" => $allRequests->count(),
                "total" => $allRequests->count(),
            ];
        } else {
            $requests = $query->orderBy('updated_at', 'desc')->paginate($perPage);
        }
        
        $counts = [
            'total'      => ModelsRequest::count(),
            'allowance' => ModelsRequest::where('type', 'allowance')->count(),
            'delegated'      => ModelsRequest::where('type', 'delegated')->count(),
            'trip_ticket'      => ModelsRequest::where('type', 'trip-ticket')->count(),
            'emergency'       => ModelsRequest::where('type', 'emergency')->count(),
        ];

        $sources = Source::all();

        $billingDates = ModelsRequest::select('billing_date')
            ->whereNotNull('billing_date')
            ->groupBy('billing_date')
            ->orderBy('billing_date', 'asc')
            ->pluck('billing_date');

        return response()->json([
            "requests" => $requests,
            "counts" => $counts,
            "sources" => $sources ?? [],
            "billing_dates" => $billingDates ?? [],
            "grand_total" => number_format($grandTotal, 2),
        ]);
    }

    public function show($id){
        $request = ModelsRequest::with(["tripTickets.rows", "logs", "source"])->findOrFail($id);
        $barangays = Barangay::all();
        $employee = EmployeeService::fetchActiveEmployee($request->employeeid);

        $employeeData = new EmployeeWithBalanceResource($employee);
        
        return response()->json([
            "data" => $request,
            "barangays" => $barangays,
            "employee_data" => $employeeData,
        ]);
    }
    
    public function store(Request $request){
        $type = $request->type;

        RequestService::isCapableOfRequesting($request->employeeid);

        if($type === "trip-ticket"){
            if($request->fuel_type === "Gasoline" || $request->fuel_type === "Diesel"){
                $request->validate([
                    "employeeid" => "required|integer",
                    "requested_by" => "required|string",
                    "department" => "required|string",
                    "division" => "nullable|string",
                    "plate_number" => "required|string",
                    "fuel_divisor" => "required|numeric",
                    "purpose" => "required|string|max:255",
                    "quantity" => "required|numeric|min:1",
                    "unit" => "required|string",
                    "fuel_type_id" => "required|string",
                    "fuel_type" => "required|string",
                    "type" => "required|string",
                    // "source" => "required|string",
                    "source_id" => "required|integer",
                    "reference_number" => "required|sometimes|string|unique:requests,reference_number",
                    "date" => "nullable|sometimes|string",
                    
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
                    "fuel_divisor" => "nullable|numeric",
                    "purpose" => "required|string|max:255",
                    "quantity" => "required|numeric|min:1",
                    "unit" => "required|string",
                    "fuel_type_id" => "required|string",
                    "fuel_type" => "required|string",
                    "type" => "required|string",
                    // "source" => "required|string",
                    "source_id" => "required|integer",
                    "reference_number" => "required|sometimes|string|unique:requests,reference_number",
                    "date" => "nullable|sometimes|string",
                ]);

                //4T AND 2T OF TRIP TICKETS IS SAVED AS TRIP-TICKET-ALLOWANCE IN TYPE IN THE FUEL_ALLOWANCES TABLE
                $currentBalance = AllowanceService::getBalance($request->employeeid, "trip-ticket");

                if($request->quantity > $currentBalance){
                    throw ValidationException::withMessages([
                        'balance' => ["Insufficient Balance. Please reload the page."],
                    ]);
                }
            }
        } else if ($type === "allowance") {
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "fuel_divisor" => "nullable|numeric",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                // "source" => "required|string",
                "source_id" => "required|integer",
                "reference_number" => "required|sometimes|string|unique:requests,reference_number",
                "date" => "nullable|sometimes|string",
            ]);

            $currentBalance = AllowanceService::getBalance($request->employeeid, $this->getAllowanceType($request->fuel_type));

            if($request->quantity > $currentBalance){
                throw ValidationException::withMessages([
                    'balance' => ["Insufficient Balance. Please reload the page."],
                ]);
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
                "fuel_divisor" => "nullable|numeric",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                // "source" => "required|string",
                "source_id" => "required|integer",
                "reference_number" => "required|sometimes|string|unique:requests,reference_number",
                "date" => "nullable|sometimes|string",
            ]);

            $currentBalance = AllowanceService::getBalance($request->employeeid, $this->getAllowanceType($request->fuel_type));

            if($request->quantity > $currentBalance){
                throw ValidationException::withMessages([
                    'balance' => ["Insufficient Balance. Please reload the page."],
                ]);
            }
        } else if ($type === "emergency"){
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "fuel_divisor" => "nullable|numeric",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                // "source" => "required|string",
                "source_id" => "required|integer",
                "reference_number" => "required|sometimes|string|unique:requests,reference_number",
                "date" => "nullable|sometimes|string",
            ]);
        }

        $itemBalance = BalanceWarehouseService::getItemBalance($request->fuel_type_id);

        if($request->quantity > $itemBalance){
            throw ValidationException::withMessages([
                'balance' => ["Insufficient stock. Only {$itemBalance} left in inventory."],
            ]);
        }

        try {

            DB::beginTransaction();

            $reference_number = 'REF-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
            
            // Log::info($request->plate_number);

            $fuelRequest = ModelsRequest::create([
                "employeeid" => $request->employeeid,
                "requested_by" => $request->requested_by,

                "delegatedtoid" => $type === "delegated" ? $request->delegatedtoid : null,
                "delegated_to" => $type === "delegated" ? $request->delegated_to : null,

                "department" => $request->department,

                "division" => $request->division ?? null,

                "vehicle_id" => $request->plate_number ? VehicleService::fetchVehicle($request->plate_number)->id : null,
                "fuel_divisor" => $request->fuel_divisor ?? null,
                "purpose" => $request->purpose,
                "quantity" => $request->quantity,
                "unit" => $request->unit,
                "fuel_type_id" => $request->fuel_type_id,
                "fuel_type" => $request->fuel_type,
                "type" => $request->type,

                // "source" => $request->source,
                "source_id" => $request->source_id,

                "date" => $request->date ?? Carbon::now(),

                "reference_number" => $request->reference_number ?? $reference_number,
            ]);


            if($fuelRequest && $type === "trip-ticket"){
                $tripTicket = TripTicket::create([
                    "request_id" => $fuelRequest->id,
                    "plate_number" => $request->plate_number ?? null,
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

    public function update(Request $request, $id){
        $type = $request->type;

        if($type === "trip-ticket"){
            if($request->fuel_type === "Gasoline" || $request->fuel_type === "Diesel"){
                $request->validate([
                    "employeeid" => "required|integer",
                    "requested_by" => "required|string",
                    "department" => "required|string",
                    "division" => "nullable|string",
                    "plate_number" => "required|string",
                    "fuel_divisor" => "nullable|sometimes|numeric",
                    "purpose" => "required|string|max:255",
                    "quantity" => "required|numeric|min:1",
                    "unit" => "required|string",
                    "fuel_type_id" => "required|string",
                    "fuel_type" => "required|string",
                    "type" => "required|string",
                    // "source" => "required|string",
                    "source_id" => "required|integer",
                    // "reference_number" => "required|string|unique:requests,reference_number",
                    "date" => "required|string",
        
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
                    "fuel_divisor" => "nullable|numeric",
                    "purpose" => "required|string|max:255",
                    "quantity" => "required|numeric|min:1",
                    "unit" => "required|string",
                    "fuel_type_id" => "required|string",
                    "fuel_type" => "required|string",
                    "type" => "required|string",
                    // "source" => "required|string",
                    "source_id" => "required|integer",
                    // "reference_number" => "required|string|unique:requests,reference_number",
                    "date" => "required|string",
                ]);

                //4T AND 2T OF TRIP TICKETS IS SAVED AS TRIP-TICKET-ALLOWANCE IN TYPE IN THE FUEL_ALLOWANCES TABLE
                $currentBalance = AllowanceService::getBalance($request->employeeid, "trip-ticket");

                if($request->quantity > $currentBalance){
                    throw ValidationException::withMessages([
                        'balance' => ["Insufficient Balance. Please reload the page."],
                    ]);
                }
            }
        } else if ($type === "allowance") {
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "fuel_divisor" => "nullable|numeric",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                // "source" => "required|string",
                "source_id" => "required|integer",
                // "reference_number" => "required|string|unique:requests,reference_number",
                "date" => "required|string",
            ]);

            $currentBalance = AllowanceService::getBalance($request->employeeid, $this->getAllowanceType($request->fuel_type));

            if($request->quantity > $currentBalance){
                throw ValidationException::withMessages([
                    'balance' => ["Insufficient Balance. Please reload the page."],
                ]);
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
                "fuel_divisor" => "nullable|numeric",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                // "source" => "required|string",
                "source_id" => "required|integer",
                // "reference_number" => "required|string|unique:requests,reference_number",
                "date" => "required|string",
            ]);

            $currentBalance = AllowanceService::getBalance($request->employeeid, $this->getAllowanceType($request->fuel_type));

            if($request->quantity > $currentBalance){
                throw ValidationException::withMessages([
                    'balance' => ["Insufficient Balance. Please reload the page."],
                ]);
            }
        } else if ($type === "emergency"){
            $request->validate([
                "employeeid" => "required|integer",
                "requested_by" => "required|string",
                "department" => "required|string",
                "division" => "nullable|string",
                "plate_number" => "nullable|string",
                "fuel_divisor" => "nullable|numeric",
                "purpose" => "required|string|max:255",
                "quantity" => "required|numeric|min:1",
                "unit" => "required|string",
                "fuel_type_id" => "required|string",
                "fuel_type" => "required|string",
                "type" => "required|string",
                // "source" => "required|string",
                "source_id" => "required|integer",
                // "reference_number" => "required|string|unique:requests,reference_number",
                "date" => "required|string",
            ]);
        }

        $itemBalance = BalanceWarehouseService::getItemBalance($request->fuel_type_id);

        if($request->quantity > $itemBalance){
            throw ValidationException::withMessages([
                'balance' => ["Insufficient stock. Only {$itemBalance} left in inventory."],
            ]);
        }

        try {

            DB::beginTransaction();

            $fuelRequest = ModelsRequest::findOrFail($id);
            $fuelRequestBeforeUpdate = clone $fuelRequest;

            $fuelRequest->update([
                "employeeid" => $request->employeeid,
                "requested_by" => $request->requested_by,

                "delegatedtoid" => $type === "delegated" ? $request->delegatedtoid : null,
                "delegated_to" => $type === "delegated" ? $request->delegated_to : null,

                "department" => $request->department,

                "division" => $request->division ?? null,

                "vehicle_id" => $request->plate_number ? VehicleService::fetchVehicle($request->plate_number)->id : null,
                "fuel_divisor" => $request->fuel_divisor ?? $fuelRequest->fuel_divisor,
                "purpose" => $request->purpose,
                "quantity" => $request->quantity,
                "unit" => $request->unit,
                "fuel_type_id" => $request->fuel_type_id,
                "fuel_type" => $request->fuel_type,
                "type" => $request->type,

                // "source" => $request->source,
                "source_id" => $request->source_id,

                "date" => $request->date,

                // "reference_number" => $request->reference_number,
            ]);


            if($fuelRequest && $type === "trip-ticket"){
                $tripTicket = TripTicket::where("request_id", $fuelRequest->id)->first();

                if ($tripTicket) {
                    // Remove all old trip ticket rows first
                    TripTicketRow::where("trip_ticket_id", $tripTicket->id)->delete();

                    // Update base trip ticket info
                    $tripTicket->update([
                        "plate_number" => $request->plate_number ?? null,
                        "driver" => $fuelRequest->requested_by,
                        "date" => Carbon::now(),
                    ]);
                } else {
                    // If no trip ticket exists yet, create a new one
                    $tripTicket = TripTicket::create([
                        "request_id" => $fuelRequest->id,
                        "plate_number" => $request->plate_number ?? null,
                        "driver" => $fuelRequest->requested_by,
                        "date" => Carbon::now(),
                    ]);
                }

                // ✅ Save new trip ticket rows
                if ($request->tripTickets && count($request->tripTickets) > 0) {
                    foreach ($request->tripTickets as $item) {
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

            $changed = $fuelRequest->getChanges();
            $before = collect($changed)->mapWithKeys(fn($v, $k) => [$k => $fuelRequestBeforeUpdate->$k])->toArray();

            ActivityLogger::log([
                'action' => 'update',
                'request' => $fuelRequest,
                'before' => $before,
                'after' => $changed,
            ]);
            
            DB::commit();
            
            return response()->json([
                "data" => $fuelRequest,
                "message" => "Request Successfully Updated", 
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

    }

    public function updateStatus(Request $request, $id){
        $fuelRequest = ModelsRequest::findOrFail($id);
        $fuelRequestBeforeUpdate = clone $fuelRequest;

        if($request->status === "released"){
            if($fuelRequest->source_id === 1){
                $validated = $request->validate([
                    "status" => "required|string|in:pending,approved,rejected,released,cancelled,undo",
                    "released_to" => "required|string",
                    "remarks" => "nullable|string",
                ]);
            } else {
                $validated = $request->validate([
                    "status" => "required|string|in:pending,approved,rejected,released,cancelled,undo",
                    "released_to" => "required|string",
                    "remarks" => "nullable|string",
                    'billing_date' => "required|string",
                    'released_date' => "required|string",
                    'unit_price' => 'required|numeric|min:1',
                ]);
            }
        } else {
            $validated = $request->validate([
                "status" => "required|string|in:pending,approved,rejected,released,cancelled,undo",
            ]);
        }
        

        try {
            DB::beginTransaction();

            if($fuelRequest){
                if($validated["status"] !== "undo"){
                    
                    if($validated["status"] === "approved" || $validated["status"] === "released"){
                        
                        $itemBalance = BalanceWarehouseService::getItemBalance($fuelRequest->fuel_type_id);
    
                        if($fuelRequest->quantity > $itemBalance){
                            throw ValidationException::withMessages([
                                'balance' => ["Insufficient stock. Only {$itemBalance} left in inventory."],
                            ]);
                        }
                        
                        if(($fuelRequestBeforeUpdate->type === "trip-ticket" && ($fuelRequestBeforeUpdate->fuel_type === "4T" || $fuelRequestBeforeUpdate->fuel_type === "2T")) || $fuelRequestBeforeUpdate->type === "allowance" || $fuelRequestBeforeUpdate->type === "delegated"){
                            //4T AND 2T OF TRIP TICKETS IS SAVED AS TRIP-TICKET-ALLOWANCE IN TYPE IN THE FUEL_ALLOWANCES TABLE
                            $currentBalance = AllowanceService::getBalance($fuelRequestBeforeUpdate->employeeid,
                            $fuelRequestBeforeUpdate->type === "trip-ticket" && ($fuelRequestBeforeUpdate->fuel_type === "4T" || $fuelRequestBeforeUpdate->fuel_type === "2T") ? "trip-ticket" : $this->getAllowanceType($fuelRequestBeforeUpdate->fuel_type));
                            
                            if($fuelRequestBeforeUpdate->quantity > $currentBalance){
                                throw ValidationException::withMessages([
                                    'balance' => ["Insufficient Balance."],
                                ]);
                            }
                        }
                    }

                    $user = $request->user();

                    if($validated["status"] === "approved"){
                        $fuelRequest->update([
                            "status" => $validated["status"],
                            "approved_by" => $user->name,
                            "approved_date" => Carbon::now(),
                            "released_by" => null,
                            "released_date" => null,
                            "released_to" => null,
                            "remarks" => null,
                            "billing_date" => null,
                            "unit_price" => null,
                        ]);
                    } else if ($validated["status"] === "released") {
                        $fuelRequest->update([
                            "status" => $validated["status"],
                            "released_by" => $user->name,
                            "released_date" => Carbon::now(),
                            "released_to" => $validated["released_to"],
                            "remarks" => $validated["remarks"],
                            "billing_date" => $validated["billing_date"] ?? null,
                            "unit_price" => $validated["unit_price"] ?? null,
                        ]);
                    } else {
                        $fuelRequest->update([
                            "status" => $validated["status"],
                            "approved_by" => null,
                            "approved_date" => null,
                            "released_by" => null,
                            "released_date" => null,
                            "released_to" => null,
                            "remarks" => null,
                            "billing_date" => null,
                            "unit_price" => null,
                        ]);
                    }

                }
    
                // FOR ALLOWANCE AND DELEGATED
                if($validated["status"] === "released" && $fuelRequest->source_id == 1){
                    //PUT THE MINUS LOGIC HERE
                    //if the status is released, it should subtract from the current balance from the main items - warehousing
                    //base the minus logic on the item id from the warehousing

                    $itemWarehousing = Item::where('id', $fuelRequest->fuel_type_id)->first();

                    if($itemWarehousing){
                        $itemWarehousing->update([
                            'QuantityOnHand' => $itemWarehousing->QuantityOnHand - $fuelRequest->quantity,
                        ]);

                        TransactionWarehousing::create([
                            'ItemID' => $fuelRequest->fuel_type_id,
                            'ReferenceNo' => $fuelRequest->reference_number,
                            'ReferenceType' => 'FMS',
                            'TransactionType' => 'OUT',
                            'Quantity' => -$fuelRequest->quantity,
                            'CreatedBy' => $request->user()->name,
                            'CreatedOn' => now(),
                        ]);
                    }

                    if($fuelRequest->type === "allowance" || $fuelRequest->type === "delegated"){

                        AllowanceService::use($fuelRequest->employeeid, $this->getAllowanceType($fuelRequest->fuel_type), $fuelRequest->quantity, $fuelRequest->id);

                    } else if ($fuelRequest->type === "trip-ticket" && $this->getAllowanceType($fuelRequest->fuel_type) === "2t4t"){

                        MilestoneAllowanceService::releaseFuel($fuelRequest->employeeid, $fuelRequest->quantity, "fuel-request:{$fuelRequest->id}");

                    } else if ($fuelRequest->type === "trip-ticket" && $this->getAllowanceType($fuelRequest->fuel_type) === "gasoline-diesel"){

                        MilestoneAllowanceService::calculateMilestone($fuelRequest->employeeid);
                        //THIS IS FOR CHECKING IF THE EMPLOYEE REACHED THE MILESTONE, IF IT REACHED THE MILESTONE, IT WILL ADD QUANTITY - THIS IS FOR 2t4t - ALSO DECLARED AS TRIPTICKET-ALLOWANCE
                    }
                }
                
                if ($validated["status"] === "undo"){
                    
                    if($fuelRequestBeforeUpdate["status"] === "released"){
                        //PUT THE ADD LOGIC HERE
                        //if the request status before undoing is released, it should add back the quantity subtracted to the current balance from the main items - warehousing
                        RequestService::isCapableOfRequesting($fuelRequest->employeeid);

                        if($fuelRequest->source_id == 1){
                            $itemWarehousing = Item::where('id', $fuelRequest->fuel_type_id)->first();
    
                            if($itemWarehousing){
                                $itemWarehousing->update([
                                    'QuantityOnHand' => $itemWarehousing->QuantityOnHand + $fuelRequest->quantity,
                                ]);
    
                                TransactionWarehousing::create([
                                    'ItemID' => $fuelRequest->fuel_type_id,
                                    'ReferenceNo' => $fuelRequest->reference_number,
                                    'ReferenceType' => 'FMS',
                                    'TransactionType' => 'IN',
                                    'Quantity' => $fuelRequest->quantity,
                                    'CreatedBy' => $request->user()->name,
                                    'CreatedOn' => now(),
                                ]);
                            }
                            
                            if($fuelRequest->type === "allowance" || $fuelRequest->type === "delegated"){
    
                                AllowanceService::undo($fuelRequest->employeeid, $this->getAllowanceType($fuelRequest->fuel_type), $fuelRequest->quantity, $fuelRequest->id);
    
                            } else if ($fuelRequest->type === "trip-ticket" && $this->getAllowanceType($fuelRequest->fuel_type) === "2t4t"){
    
                                // AllowanceService::undo($fuelRequest->employeeid, 'trip-ticket', $fuelRequest->quantity, $fuelRequest->id);
                                MilestoneAllowanceService::undoFuelRelease($fuelRequest->employeeid, "fuel-request:{$fuelRequest->id}");
    
                            } else if ($fuelRequest->type === "trip-ticket" && $this->getAllowanceType($fuelRequest->fuel_type) === "gasoline-diesel"){
                                MilestoneAllowanceService::calculateMilestone($fuelRequest->employeeid);
                            }
                        }
                        
                        $fuelRequest->update([
                            "status" => "approved",
                            "released_by" => null,
                            "released_date" => null,
                            "released_to" => null,
                            "remarks" => null,
                            "billing_date" => null,
                            "unit_price" => null,
                        ]);
    

                    } else if ($fuelRequestBeforeUpdate["status"] === "rejected" || $fuelRequestBeforeUpdate["status"] === "cancelled" || $fuelRequestBeforeUpdate["status"] === "approved"){
                        if($fuelRequestBeforeUpdate["status"] != "approved"){
                            RequestService::isCapableOfRequesting($fuelRequest->employeeid);
                        }

                        $fuelRequest->update([
                            "status" => "pending",
                            "approved_by" => null,
                            "approved_date" => null,
                            "released_by" => null,
                            "released_date" => null,
                            "released_to" => null,
                            "remarks" => null,
                            "billing_date" => null,
                            "unit_price" => null,
                        ]);
                    }
                }

                ActivityLogger::log([
                    'action' => $validated['status'],
                    'request' => $fuelRequest,
                    'requestBeforeUpdate' => $fuelRequestBeforeUpdate,
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

    public function checkIfCapableOfCreatingRequest($employeeid){
        RequestService::isCapableOfRequesting($employeeid);

        return response()->json([
            "status" => true,
        ]);
    }

    public function bulkUpdateStatus(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
            'billing_date' => "required|string",
            'released_date' => "required|string",
            'unit_price' => 'required|numeric|min:1',
        ]);

        try {
            DB::beginTransaction();

            $fuelRequests = ModelsRequest::whereIn('reference_number', $validated['ids'])->get();

            foreach($fuelRequests as $fuelRequest){
                $fuelRequestBeforeUpdate = clone $fuelRequest;

                $fuelRequest->update([
                    'billing_date' => $validated['billing_date'],
                    'released_date' => $validated['released_date'],
                    'unit_price' => $validated['unit_price'],
                    'status' => 'released',
                    'released_by' => $request->user()->name,
                    'released_to' => $fuelRequest->requested_by,
                ]);

                ActivityLogger::log([
                    'action' => 'released',
                    'request' => $fuelRequest,
                    'requestBeforeUpdate' => $fuelRequestBeforeUpdate,
                ]);
            }

            DB::commit();

            return response()->json(['message' => 'Requests updated successfully']);
        } catch (\Exception $e){
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

    private function getAllowanceType(string $type){
        $fuelType = '';

        if($type === "Diesel" || $type === "Gasoline"){
            $fuelType = 'gasoline-diesel';
        } else if ($type === "4T" || $type === "2T"){
            $fuelType = '2t4t';
        } else if ($type === "B-fluid"){
            $fuelType = 'bfluid';
        }

        return $fuelType;
    }
}
