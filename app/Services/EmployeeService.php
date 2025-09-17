<?php

namespace App\Services;

use App\Models\FuelAllowance;
use Carbon\Carbon;
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

    public static function getLatestBalance(int $employeeId){
        $allowance = FuelAllowance::where("employeeid", $employeeId)->orderByDesc("week_start")->first();

        return $allowance;
    }

    public static function getCurrentBalance(int $employeeId): float
    {
        $allowance = EmployeeService::getLatestBalance($employeeId);

        if (!$allowance) {
            return 0.0; // No allowance exists yet
        }

        // Total available = allowance + carried_over - used - advanced
        $balance = ($allowance->allowance + $allowance->carried_over)
                 - $allowance->used
                 - $allowance->advanced;

        return $balance; // Prevent negative balances
    }
}
