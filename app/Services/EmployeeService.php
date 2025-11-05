<?php

namespace App\Services;

use App\Models\AllowanceTransaction;
use App\Models\FuelAllowance;
use App\Models\Request as FuelRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmployeeService
{
    public static function fetchActiveEmployees(){
        return DB::connection('mysql2')
            ->table('employment_setup as es')
            ->leftJoin('employee as e', 'es.employeeid', '=', 'e.employeeid')
            ->where('es.employment_code', function ($q) {
                $q->select(DB::raw('MAX(sub.employment_code)'))
                ->from('employment_setup as sub')
                ->whereColumn('sub.employeeid', 'es.employeeid')
                ->where('sub.isServiceRec', 0);
            })
            ->select(
            'e.*', 
            'es.activation_status', 
            'es.employment_code', 
            'es.dept_code', 
            'es.emp_status', 
            'es.WithUndertime', 
            'es.desig_position',
            'es.div_code'
        );
    }

    public static function fetchActiveEmployee(int $employeeid){
        return DB::connection('mysql2')
            ->table('employment_setup as es')
            ->leftJoin('employee as e', 'es.employeeid', '=', 'e.employeeid')
            ->where('es.employment_code', function ($q) {
                $q->select(DB::raw('MAX(sub.employment_code)'))
                ->from('employment_setup as sub')
                ->whereColumn('sub.employeeid', 'es.employeeid')
                ->where('sub.isServiceRec', 0);
            })
            ->where('e.employeeid', $employeeid)
            ->select(
            'e.*', 
            'es.activation_status', 
            'es.employment_code', 
            'es.dept_code', 
            'es.emp_status', 
            'es.WithUndertime', 
            'es.desig_position',
            'es.div_code'
            )->first();
    }

    public static function fetchEmployeeWithBalances()
    {
        return DB::connection('mysql2')
            ->table('employment_setup as es')
            ->leftJoin('employee as e', 'es.employeeid', '=', 'e.employeeid')
            ->where('es.employment_code', function ($q) {
                $q->select(DB::raw('MAX(sub.employment_code)'))
                    ->from('employment_setup as sub')
                    ->whereColumn('sub.employeeid', 'es.employeeid')
                    ->where('sub.isServiceRec', 0);
            })
            ->where(function ($q) {
                $q->where("es.WithUndertime", "N")
                  ->orWhere("es.desig_position", 'like', '%Manager%')
                  ->orWhere("es.desig_position", 'like', '%Supervisor%');
            })
            ->select(
                'e.*',
                'es.activation_status',
                'es.employment_code',
                'es.dept_code',
                'es.emp_status',
                'es.WithUndertime',
                'es.desig_position',
                'es.div_code'
            );
    }

    public static function fetchEmployeeWithBalance(int $employeeid)
    {
        return DB::connection('mysql2')
            ->table('employment_setup as es')
            ->leftJoin('employee as e', 'es.employeeid', '=', 'e.employeeid')
            ->where('es.employment_code', function ($q) {
                $q->select(DB::raw('MAX(sub.employment_code)'))
                    ->from('employment_setup as sub')
                    ->whereColumn('sub.employeeid', 'es.employeeid')
                    ->where('sub.isServiceRec', 0);
            })
            ->where(function ($q) {
                $q->where("es.WithUndertime", "N")
                  ->orWhere("es.desig_position", 'like', '%Manager%')
                  ->orWhere("es.desig_position", 'like', '%Supervisor%');
            })
            ->where('e.employeeid', $employeeid)
            ->select(
                'e.*',
                'es.activation_status',
                'es.employment_code',
                'es.dept_code',
                'es.emp_status',
                'es.WithUndertime',
                'es.desig_position',
                'es.div_code'
            )->first();
    }

    public static function getTotalDistanceTravelled(int $employeeid)
    {
        $fuelRequests = FuelRequest::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')->where('status', 'released')
            ->with('tripTickets.rows')
            ->get();

        $totalDistance = 0;

        foreach ($fuelRequests as $request) {
            foreach ($request->tripTickets as $ticket) {
                foreach ($ticket->rows as $row) {
                    $totalDistance += $row->distance ?? 0;
                }
            }
        }

        return $totalDistance;
    }

    public static function getTotalFuelConsumed(int $employeeid){
        $fuelRequests = FuelRequest::where('employeeid', $employeeid)->where('status', 'released')->get();

        $totalFuelConsumed = 0;

        foreach ($fuelRequests as $request) {
            $totalFuelConsumed += $request->quantity ?? 0;
        }

        return $totalFuelConsumed;
    }

    public static function getDistanceSinceLastIssue(int $employeeid): array
    {
        $milestone = SettingService::getLatestMilestoneSettings()->value;

        // Get the last "use" transaction for trip-ticket
        $lastUse = AllowanceTransaction::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')
            ->where('tx_type', 'use')
            ->orderByDesc('id')
            ->first();

        $query = FuelRequest::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')
            ->where('status', 'released')
            ->with('tripTickets.rows');

        if ($lastUse) {
            // Only consider requests after the last usage
            $query->where('updated_at', '>', $lastUse->created_at);
        }

        $fuelRequests = $query->get();

        $distance = 0;
        foreach ($fuelRequests as $request) {
            foreach ($request->tripTickets as $ticket) {
                foreach ($ticket->rows as $row) {
                    $distance += $row->distance ?? 0;
                }
            }
        }

        // Calculate milestone progress
        $remaining = max($milestone - $distance, 0);
        $reached = $distance >= $milestone;

        return [
            'distance_since_last' => $distance,
            'remaining'           => $remaining,
            'reached'             => $reached,
            // 'milestone'        => $milestone, // uncomment if needed
        ];
    }
}
