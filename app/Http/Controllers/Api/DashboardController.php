<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Inventory;
use App\Models\Request as ModelsRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // public function admin(Request $request){

    //     $employeeUsageRange = $request->input('employee_usage_range', 'all');
    //     $employeeUsageYear = $request->input('employee_usage_year', now()->year);
    //     $employeeUsageMonth = $request->input('employee_usage_month', now()->month);

    //     $fuelConsumptionRange = $request->input('fuel_consumption_range', 'all');
    //     $fuelConsumptionYear = $request->input('fuel_consumption_year', now()->year);
    //     $fuelConsumptionMonth = $request->input('fuel_consumption_month', now()->month);

    //     $fuelIssuedThisMonth = ModelsRequest::where('status', 'released')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->sum('quantity');
    //     $requestsThisMonth = ModelsRequest::where('status', 'released')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
    //     $pendingRequests = ModelsRequest::where('status', 'pending')->count();
        
    //     $distanceTravelledThisMonth = null;

    //     $tripTicketRequests = ModelsRequest::with('tripTickets.rows')
    //         ->where('type', 'trip-ticket')
    //         ->where('status', 'released')
    //         ->whereMonth('updated_at', now()->month)
    //         ->whereYear('updated_at', now()->year)
    //         ->get();

    //     $distanceTravelledThisMonth = 0;

    //     foreach ($tripTicketRequests as $request) {
    //         foreach ($request->tripTickets as $tripTicket) {
    //             foreach ($tripTicket->rows as $row) {
    //                 $distanceTravelledThisMonth += $row->distance;
    //             }
    //         }
    //     }

    //     $inventories = Inventory::with('fuelType')->get();

    //     $recentRequests = ModelsRequest::orderByDesc('id')->take(5)->get();
    //     $recentLogs = ActivityLog::orderByDesc('id')->take(5)->get();

    //     $employeeUsage = ModelsRequest::selectRaw('requested_by as name, SUM(quantity) as total')
    //         ->where('status', 'released')
    //         ->whereYear('updated_at', $employeeUsageYear)
    //         ->whereMonth('updated_at', $employeeUsageMonth)
    //         ->groupBy('name')
    //         ->orderByDesc('total')
    //         ->take(5)
    //         ->get()
    //         ->map(function ($item) {
    //             return [
    //                 'name' => $item->name,
    //                 'total' => (float) $item->total,
    //             ];
    //         })
    //         ->values(); // ensures it returns a clean indexed array

    //     // Fuel Consumption - aggregate by month for the selected year
    //     $fuelConsumptionRaw = ModelsRequest::selectRaw('MONTH(updated_at) as month, SUM(quantity) as total')
    //         ->where('status', 'released')
    //         ->whereYear('updated_at', $fuelConsumptionYear)
    //         ->groupBy('month')
    //         ->orderBy('month')
    //         ->get();

    //     $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
    //         $monthName = Carbon::create()->month($item->month)->format('M'); // e.g. "Jan"
    //         return [
    //             'name' => $monthName,
    //             'total' => (float) $item->total,
    //         ];
    //     })->values();

    //     $counts = [
    //         'fuel_issued_this_month' => $fuelIssuedThisMonth,
    //         'distance_travelled_this_month' => $distanceTravelledThisMonth,
    //         'requests_this_month' => $requestsThisMonth,
    //         'pending_requests' => $pendingRequests,
    //     ];
        
    //     return response()->json([
    //         "counts" => $counts,
    //         'inventories' => $inventories,
    //         'recent_requests' => $recentRequests,
    //         'recent_logs' => $recentLogs,
    //         'employee_usage' => $employeeUsage,
    //         'fuel_consumption' => $fuelConsumption,
    //     ], 200);
    // }

    public function admin(Request $request)
    {
        $employeeUsageRange = $request->input('employee_usage_range', 'all');
        $employeeUsageYear = $request->input('employee_usage_year', now()->year);
        $employeeUsageMonth = $request->input('employee_usage_month', now()->month);

        $fuelConsumptionRange = $request->input('fuel_consumption_range', 'all');
        $fuelConsumptionYear = $request->input('fuel_consumption_year', now()->year);
        $fuelConsumptionMonth = $request->input('fuel_consumption_month', now()->month);

        // Counts
        $fuelIssuedThisMonth = ModelsRequest::where('status', 'released')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->sum('quantity');

        $requestsThisMonth = ModelsRequest::where('status', 'released')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        $pendingRequests = ModelsRequest::where('status', 'pending')->count();

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

        $inventories = Inventory::with('fuelType')->get();
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

        if ($fuelConsumptionRange === 'yearly') {
            // Aggregate by month for selected year
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('MONTH(updated_at) as month, SUM(quantity) as total')
                ->whereYear('updated_at', $fuelConsumptionYear)
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
                $monthName = Carbon::create()->month($item->month)->format('M');
                return [
                    'name' => $monthName,
                    'total' => (float) $item->total,
                ];
            })->values();
        } elseif ($fuelConsumptionRange === 'monthly') {
            // Aggregate by day for selected month
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('DAY(updated_at) as day, SUM(quantity) as total')
                ->whereYear('updated_at', $fuelConsumptionYear)
                ->whereMonth('updated_at', $fuelConsumptionMonth)
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
                return [
                    'name' => str_pad($item->day, 2, '0', STR_PAD_LEFT), // "01", "02", ...
                    'total' => (float) $item->total,
                ];
            })->values();
        } else {
            // "all" → group by year
            $fuelConsumptionRaw = $fuelConsumptionQuery
                ->selectRaw('YEAR(updated_at) as year, SUM(quantity) as total')
                ->groupBy('year')
                ->orderBy('year')
                ->get();

            $fuelConsumption = $fuelConsumptionRaw->map(function ($item) {
                return [
                    'name' => (string) $item->year,
                    'total' => (float) $item->total,
                ];
            })->values();
        }

        $years = ModelsRequest::where('status', 'released')
            ->selectRaw('YEAR(updated_at) as year')
            ->distinct()
            ->orderBy('year', 'asc')
            ->pluck('year');

        // Counts summary
        $counts = [
            'fuel_issued_this_month' => $fuelIssuedThisMonth,
            'distance_travelled_this_month' => $distanceTravelledThisMonth,
            'requests_this_month' => $requestsThisMonth,
            'pending_requests' => $pendingRequests,
        ];

        return response()->json([
            "counts" => $counts,
            'inventories' => $inventories,
            'recent_requests' => $recentRequests,
            'recent_logs' => $recentLogs,
            'employee_usage' => $employeeUsage,
            'fuel_consumption' => $fuelConsumption,
            'years' => $years,
        ], 200);
    }
}
