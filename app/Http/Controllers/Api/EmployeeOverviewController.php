<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Request as ModelsRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Svg\Tag\Rect;

class EmployeeOverviewController extends Controller
{
    public function index(Request $request, int $employeeid){
        $range = $request->input('range');
        $year = $request->input('year');
        $month = $request->input('month');
        $week = $request->input('week');

        if ($month) {
            $month = Carbon::parse($month . ' 1 ' . $year)->month;
        }

        if ($week) {
            $week = (int) filter_var($week, FILTER_SANITIZE_NUMBER_INT);
        }
        
        $requests = ModelsRequest::with('tripTickets.rows')
            ->where('employeeid', $employeeid)
            ->where('status', 'released');

        $data = collect();

        if ($range === 'all') {
            // Group by year
            $data = $requests->get()
                ->groupBy(fn($r) => Carbon::parse($r->created_at)->format('Y'))
                ->map(function ($group, $year) {
                    return [
                        'name' => $year,
                        'distance' => $group->flatMap->tripTickets->flatMap->rows->sum('distance'),
                        'fuel' => $group->sum('quantity'),
                        'requests' => $group->count(),
                    ];
                })
                ->values();

        } elseif ($range === 'yearly' && $year) {
            // Filter by year → group by month
            $data = $requests->whereYear('created_at', $year)->get()
                ->groupBy(fn($r) => Carbon::parse($r->created_at)->format('M'))
                ->map(function ($group, $month) {
                    return [
                        'name' => $month,
                        'distance' => $group->flatMap->tripTickets->flatMap->rows->sum('distance'),
                        'fuel' => $group->sum('quantity'),
                        'requests' => $group->count(),
                    ];
                })
                ->values();

        } elseif ($range === 'monthly' && $year && $month) {
            // Filter by month → group by week
            $data = $requests->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)->get()
                ->groupBy(fn($r) => 'Week ' . Carbon::parse($r->created_at)->weekOfMonth)
                ->map(function ($group, $week) {
                    return [
                        'name' => $week,
                        'distance' => $group->flatMap->tripTickets->flatMap->rows->sum('distance'),
                        'fuel' => $group->sum('quantity'),
                        'requests' => $group->count(),
                    ];
                })
                ->values();

        } elseif ($range === 'weekly' && $year && $month && $week) {
            // Filter by specific week → group by day
            $data = $requests->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get()
                ->filter(fn($r) => Carbon::parse($r->created_at)->weekOfMonth == $week)
                ->groupBy(fn($r) => Carbon::parse($r->created_at)->format('D'))
                ->map(function ($group, $day) {
                    return [
                        'name' => $day,
                        'distance' => $group->flatMap->tripTickets->flatMap->rows->sum('distance'),
                        'fuel' => $group->sum('quantity'),
                        'requests' => $group->count(),
                    ];
                })
                ->values();
        }

        $years = ModelsRequest::where('employeeid', $employeeid)
        ->where('status', 'released')
        ->selectRaw('YEAR(created_at) as year')
        ->distinct()
        ->orderBy('year', 'asc')
        ->pluck('year');

        return response()->json([
            "data" => $data,
            "years" => $years,
        ], 200);
    }
}
