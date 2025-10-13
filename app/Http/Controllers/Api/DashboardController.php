<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeWithBalanceResource;
use App\Http\Resources\Warehousing\ItemResource;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Request as ModelsRequest;
use App\Models\Warehousing\Item;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function admin(Request $request)
    {
        $employeeUsageRange = $request->input('employee_usage_range', 'all');
        $employeeUsageYear = $request->input('employee_usage_year', now()->year);
        $employeeUsageMonth = $request->input('employee_usage_month', now()->month);

        $fuelConsumptionRange = $request->input('fuel_consumption_range', 'all');
        $fuelConsumptionYear = $request->input('fuel_consumption_year', now()->year);
        $fuelConsumptionMonth = $request->input('fuel_consumption_month', now()->month);

        // Counts
        $totalRequests = ModelsRequest::count();

        $fuelIssuedThisMonth = ModelsRequest::where('status', 'released')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('quantity');

        $requestsThisMonth = ModelsRequest::where('status', 'released')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $pendingRequests = ModelsRequest::where('status', 'pending')->count();
        $releasedRequests = ModelsRequest::where('status', 'released')->count();

        // Distance travelled this month
        $tripTicketRequests = ModelsRequest::with('tripTickets.rows')
            ->where('type', 'trip-ticket')
            ->where('status', 'released')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->get();

        $distanceTravelledThisMonth = 0;
        foreach ($tripTicketRequests as $request) {
            foreach ($request->tripTickets as $tripTicket) {
                foreach ($tripTicket->rows as $row) {
                    $distanceTravelledThisMonth += $row->distance;
                }
            }
        }

        $inventories = Item::where('InventoryTypeID', 5)->with('unit')->get();
        
        $recentRequests = ModelsRequest::orderByDesc('id')->take(5)->get();
        $recentLogs = ActivityLog::orderByDesc('id')->take(5)->get();

        // 🧩 EMPLOYEE USAGE (Top 5 employees)
        $employeeUsageQuery = ModelsRequest::selectRaw('requested_by as name, SUM(quantity) as total')
            ->where('status', 'released');

        // Apply range filter
        if ($employeeUsageRange === 'yearly') {
            $employeeUsageQuery->whereYear('updated_at', $employeeUsageYear);
        } elseif ($employeeUsageRange === 'monthly') {
            $employeeUsageQuery->whereYear('updated_at', $employeeUsageYear)
                            ->whereMonth('updated_at', $employeeUsageMonth);
        }
        // "all" means no date filter

        $employeeUsage = $employeeUsageQuery
            ->groupBy('name')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'total' => (float) $item->total,
                ];
            })
            ->values();

        // 🧩 FUEL CONSUMPTION (Monthly or Daily)
        $fuelConsumptionQuery = ModelsRequest::where('status', 'released');

        $fuelTypes = ['Gasoline', 'Diesel', '2T', '4T', 'B-fluid'];

        // if ($fuelConsumptionRange === 'yearly') {
        //     // Aggregate by month for selected year
        //     $fuelConsumptionRaw = $fuelConsumptionQuery
        //         ->selectRaw('MONTH(updated_at) as month, SUM(quantity) as total')
        //         ->whereYear('updated_at', $fuelConsumptionYear)
        //         ->groupBy('month')
        //         ->orderBy('month')
        //         ->get();

        //     $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
        //         $monthName = Carbon::create()->month($item->month)->format('M');
        //         return [
        //             'name' => $monthName,
        //             'total' => (float) $item->total,
        //         ];
        //     })->values();
        // } elseif ($fuelConsumptionRange === 'monthly') {
        //     // Aggregate by day for selected month
        //     $fuelConsumptionRaw = $fuelConsumptionQuery
        //         ->selectRaw('DAY(updated_at) as day, SUM(quantity) as total')
        //         ->whereYear('updated_at', $fuelConsumptionYear)
        //         ->whereMonth('updated_at', $fuelConsumptionMonth)
        //         ->groupBy('day')
        //         ->orderBy('day')
        //         ->get();

        //     $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
        //         return [
        //             'name' => str_pad($item->day, 2, '0', STR_PAD_LEFT), // "01", "02", ...
        //             'total' => (float) $item->total,
        //         ];
        //     })->values();
        // } else {
        //     // "all" → group by year
        //     $fuelConsumptionRaw = $fuelConsumptionQuery
        //         ->selectRaw('YEAR(updated_at) as year, SUM(quantity) as total')
        //         ->groupBy('year')
        //         ->orderBy('year')
        //         ->get();

        //     $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
        //         return [
        //             'name' => (string) $item->year,
        //             'total' => (float) $item->total,
        //         ];
        //     })->values();
        // }

        if ($fuelConsumptionRange === 'yearly') {
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('MONTH(updated_at) as month, fuel_type, SUM(quantity) as total')
                ->whereYear('updated_at', $fuelConsumptionYear)
                ->groupBy('month', 'fuel_type')
                ->orderBy('month')
                ->get();

            $fuelConsumption = collect(range(1, 12))->map(function ($month) use ($fuelConsumptionRaw, $fuelTypes) {
                $monthData = ['name' => Carbon::create()->month($month)->format('M')];
                $total = 0;

                foreach ($fuelTypes as $type) {
                    $amount = (float) ($fuelConsumptionRaw
                        ->where('month', $month)
                        ->where('fuel_type', $type)
                        ->first()
                        ->total ?? 0);

                    $monthData[$type] = $amount;
                    $total += $amount;
                }

                $monthData['total'] = $total;

                return $monthData;
            })->values();

        } elseif ($fuelConsumptionRange === 'monthly') {
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('DAY(updated_at) as day, fuel_type, SUM(quantity) as total')
                ->whereYear('updated_at', $fuelConsumptionYear)
                ->whereMonth('updated_at', $fuelConsumptionMonth)
                ->groupBy('day', 'fuel_type')
                ->orderBy('day')
                ->get();

            $daysInMonth = Carbon::create($fuelConsumptionYear, $fuelConsumptionMonth)->daysInMonth;

            $fuelConsumption = collect(range(1, $daysInMonth))->map(function ($day) use ($fuelConsumptionRaw, $fuelTypes) {
                $dayData = ['name' => str_pad($day, 2, '0', STR_PAD_LEFT)];
                $total = 0;

                foreach ($fuelTypes as $type) {
                    $amount = (float) ($fuelConsumptionRaw
                        ->where('day', $day)
                        ->where('fuel_type', $type)
                        ->first()
                        ->total ?? 0);

                    $dayData[$type] = $amount;
                    $total += $amount;
                }

                $dayData['total'] = $total;

                return $dayData;
            })->values();

        } else {
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('YEAR(updated_at) as year, fuel_type, SUM(quantity) as total')
                ->groupBy('year', 'fuel_type')
                ->orderBy('year')
                ->get();

            $years = $fuelConsumptionRaw->pluck('year')->unique()->sort();

            $fuelConsumption = $years->map(function ($year) use ($fuelConsumptionRaw, $fuelTypes) {
                $yearData = ['name' => (string) $year];
                $total = 0;

                foreach ($fuelTypes as $type) {
                    $amount = (float) ($fuelConsumptionRaw
                        ->where('year', $year)
                        ->where('fuel_type', $type)
                        ->first()
                        ->total ?? 0);

                    $yearData[$type] = $amount;
                    $total += $amount;
                }

                $yearData['total'] = $total;

                return $yearData;
            })->values();
        }

        $years = ModelsRequest::where('status', 'released')
            ->selectRaw('YEAR(updated_at) as year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year');

        // Counts summary
        $counts = [
            // 'fuel_issued_this_month' => $fuelIssuedThisMonth,
            // 'distance_travelled_this_month' => $distanceTravelledThisMonth,
            // 'requests_this_month' => $requestsThisMonth,
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'released_requests' => $releasedRequests,
        ];

        return response()->json([
            "counts" => $counts,
            'inventories' => ItemResource::collection($inventories),
            'recent_requests' => $recentRequests,
            'recent_logs' => $recentLogs,
            'employee_usage' => $employeeUsage,
            'fuel_consumption' => $fuelConsumption,
            'years' => $years,
        ], 200);
    }

    public function user(Request $request, int $id){
        $employee = EmployeeService::fetchActiveEmployee($id);
        
        $fuelConsumptionRange = $request->input('fuel_consumption_range', 'all');
        $fuelConsumptionYear = $request->input('fuel_consumption_year', now()->year);
        $fuelConsumptionMonth = $request->input('fuel_consumption_month', now()->month);

        $fuelConsumptionQuery = ModelsRequest::where('status', 'released')->where("employeeid", $employee->employeeid);

        $fuelTypes = ['Gasoline', 'Diesel', '2T', '4T', 'B-fluid'];


        if ($fuelConsumptionRange === 'yearly') {
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('MONTH(updated_at) as month, fuel_type, SUM(quantity) as total')
                ->whereYear('updated_at', $fuelConsumptionYear)
                ->groupBy('month', 'fuel_type')
                ->orderBy('month')
                ->get();

            $fuelConsumption = collect(range(1, 12))->map(function ($month) use ($fuelConsumptionRaw, $fuelTypes) {
                $monthData = ['name' => Carbon::create()->month($month)->format('M')];
                $total = 0;

                foreach ($fuelTypes as $type) {
                    $amount = (float) ($fuelConsumptionRaw
                        ->where('month', $month)
                        ->where('fuel_type', $type)
                        ->first()
                        ->total ?? 0);

                    $monthData[$type] = $amount;
                    $total += $amount;
                }

                $monthData['total'] = $total;

                return $monthData;
            })->values();

        } elseif ($fuelConsumptionRange === 'monthly') {
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('DAY(updated_at) as day, fuel_type, SUM(quantity) as total')
                ->whereYear('updated_at', $fuelConsumptionYear)
                ->whereMonth('updated_at', $fuelConsumptionMonth)
                ->groupBy('day', 'fuel_type')
                ->orderBy('day')
                ->get();

            $daysInMonth = Carbon::create($fuelConsumptionYear, $fuelConsumptionMonth)->daysInMonth;

            $fuelConsumption = collect(range(1, $daysInMonth))->map(function ($day) use ($fuelConsumptionRaw, $fuelTypes) {
                $dayData = ['name' => str_pad($day, 2, '0', STR_PAD_LEFT)];
                $total = 0;

                foreach ($fuelTypes as $type) {
                    $amount = (float) ($fuelConsumptionRaw
                        ->where('day', $day)
                        ->where('fuel_type', $type)
                        ->first()
                        ->total ?? 0);

                    $dayData[$type] = $amount;
                    $total += $amount;
                }

                $dayData['total'] = $total;

                return $dayData;
            })->values();

        } else {
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('YEAR(updated_at) as year, fuel_type, SUM(quantity) as total')
                ->groupBy('year', 'fuel_type')
                ->orderBy('year')
                ->get();

            $years = $fuelConsumptionRaw->pluck('year')->unique()->sort();

            $fuelConsumption = $years->map(function ($year) use ($fuelConsumptionRaw, $fuelTypes) {
                $yearData = ['name' => (string) $year];
                $total = 0;

                foreach ($fuelTypes as $type) {
                    $amount = (float) ($fuelConsumptionRaw
                        ->where('year', $year)
                        ->where('fuel_type', $type)
                        ->first()
                        ->total ?? 0);

                    $yearData[$type] = $amount;
                    $total += $amount;
                }

                $yearData['total'] = $total;

                return $yearData;
            })->values();
        }

        $years = ModelsRequest::where('status', 'released')->where('employeeid', $employee->employeeid)
            ->selectRaw('YEAR(updated_at) as year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year');


        $totalRequests = ModelsRequest::where("employeeid", $employee->employeeid)->count();
        $pendingRequests = ModelsRequest::where("status", "pending")->where("employeeid", $employee->employeeid)->count();
        $releasedRequests = ModelsRequest::where("status", "released")->where("employeeid", $employee->employeeid)->count();

        $counts = [
            'total_requests' => $totalRequests,
            'pending_requests' => $pendingRequests,
            'released_requests' => $releasedRequests,
        ];


        return response()->json([
            "employee" => new EmployeeWithBalanceResource($employee),
            "counts" => $counts,
            "fuel_consumption" => $fuelConsumption,
            "years" => $years,
        ], 200);
    }
}
