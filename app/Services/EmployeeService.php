<?php

namespace App\Services;

use App\Models\FuelAllowance;
use App\Models\Request as FuelRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

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
                  ->orWhere("es.desig_position", 'like', '%Manager%');
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

    public static function getLatestBalance(int $employeeId, string $type){
        // $allowance = FuelAllowance::where("employeeid", $employeeId)->where('type', $type)->orderByDesc("week_start")->first();
        $allowance = FuelAllowance::where("employeeid", $employeeId)->where('type', $type)->orderByDesc("id")->first();

        return $allowance;
    }

    public static function getCurrentBalance(int $employeeId, string $type): float
    {
        $allowance = EmployeeService::getLatestBalance($employeeId, $type);

        if (!$allowance) {
            return 0.0; // No allowance exists yet
        }

        // Total available = allowance + carried_over - used - advanced
        $balance = ($allowance->allowance + $allowance->carried_over)
                 - $allowance->used
                 - $allowance->advanced;

        return $balance; // Prevent negative balances
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

    public static function checkTripTicketAllowance(int $employeeid): void
    {
        $milestone = self::milestone();

        $totalDistance = self::getTotalDistanceTravelled($employeeid);

        // How many milestones of 5000 km the employee has reached
        $milestones = floor($totalDistance / $milestone);

        // Count how many trip-ticket-allowances already exist
        $existing = FuelAllowance::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket-allowance')
            ->count();

        // If milestones > existing, create missing rows
        if ($milestones > $existing) {
            for ($i = $existing + 1; $i <= $milestones; $i++) {
                // ✅ Find last allowance for carry-over/advance
                $lastAllowance = FuelAllowance::where('employeeid', $employeeid)
                    ->where('type', 'trip-ticket-allowance')
                    ->orderByDesc('id')
                    ->first();

                $carriedOver = 0;
                $advanced = 0;

                if ($lastAllowance) {
                    $lastBalance = ($lastAllowance->allowance + $lastAllowance->carried_over)
                                - ($lastAllowance->used + $lastAllowance->advanced);

                    if ($lastBalance > 0) {
                        $carriedOver = $lastBalance;
                    } elseif ($lastBalance < 0) {
                        $advanced = abs($lastBalance);
                    }
                }

                FuelAllowance::create([
                    'employeeid'   => $employeeid,
                    'week_start'   => Carbon::now(),
                    'allowance'    => 1,
                    'carried_over' => $carriedOver,
                    'used'         => 0,
                    'advanced'     => $advanced,
                    'type'         => 'trip-ticket-allowance',
                ]);
            }
        }
    }

    public static function getDistanceSinceLastIssue(int $employeeid): array
    {
        $milestone = self::milestone();

        // Get the last trip-ticket allowance
        $lastAllowance = FuelAllowance::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket-allowance')->where('used', '>', 0)
            ->orderByDesc('id')
            ->first();

        $query = FuelRequest::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')
            ->with('tripTickets.rows');

        if ($lastAllowance) {
            $query->where('created_at', '>', $lastAllowance->updated_at);
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
            'milestone'           => $milestone,
            'remaining'           => $remaining,
            'reached'             => $reached,
        ];
    }

    public static function milestone(){
        $milestone = Config::get('fuel_allowances.trip-ticket-allowance.milestone', 5000);
        return $milestone;
    }

}
