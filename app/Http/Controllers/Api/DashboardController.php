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
    public function admin(){


        $fuelIssuedThisMonth = ModelsRequest::where('status', 'released')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->sum('quantity');
        $requestsThisMonth = ModelsRequest::where('status', 'released')->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();
        $pendingRequests = ModelsRequest::where('status', 'pending')->count();
        
        $distanceTravelledThisMonth = null;

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
        ], 200);
    }
}
