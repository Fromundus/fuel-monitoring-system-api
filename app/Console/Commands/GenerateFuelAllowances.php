<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AllowanceTransaction;
use App\Models\Employee;
use App\Models\FuelType;
use App\Services\ActivityLogger;
use App\Services\EmployeeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GenerateFuelAllowances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * --weeks lets you move forward/back in time
     */
    protected $signature = 'fuel:generate {--weeks=0 : Offset weeks from the current week}';

    /**
     * The console command description.
     */
    protected $description = 'Generate fuel allowances for all employees based on rules';

    public function handle()
    {
        $offsetWeeks = (int) $this->option('weeks');
        $weekStart   = Carbon::now()->startOfWeek()->addWeeks($offsetWeeks);

        $employees = EmployeeService::fetchEmployeeWithBalances()->get();

        // You can move this config to config/fuel_allowances.php
        $rules = [
            'gasoline-diesel' => ['frequency' => 'weekly',     'amount' => 8],
            '2t4t'            => ['frequency' => 'bi-monthly', 'amount' => 1],
            'b-fluid'         => ['frequency' => 'quarterly',  'amount' => 1],
        ];

        try {

            DB::beginTransaction();

            foreach ($employees as $employee) {
                foreach ($rules as $type => $rule) {
                    if (! $this->shouldCreateForWeek($rule['frequency'], $weekStart)) {
                        continue;
                    }
    
                    // Prevent duplicate grants for same employee + type + week
                    $exists = AllowanceTransaction::where('employeeid', $employee->employeeid)
                        ->where('type', $type)
                        ->where('tx_type', 'grant')
                        ->whereDate('granted_at', $weekStart->toDateString())
                        ->exists();
    
                    if ($exists) {
                        continue;
                    }
    
                    AllowanceTransaction::create([
                        'employeeid' => $employee->employeeid,
                        'type'       => $type,
                        'tx_type'    => 'grant',
                        'quantity'   => $rule['amount'],
                        'reference'  => "system:{$type}:{$weekStart->toDateString()}",
                        'granted_at' => $weekStart,
                    ]);

                    $fuelName = "";

                    if($type === "gasoline-diesel"){
                        $fuelName = "Gasoline/Diesel";
                    } else if ($type === "2t4t"){
                        $fuelName = "2T/4T Oil";
                    } else if ($type === "b-fluid"){
                        $fuelName = "Break Fluid";
                    }


                    if($type == "gasoline-diesel"){
                        ActivityLogger::log([
                            "action" => "granted",
                            "employeeid" => $employee->employeeid,
                            "description" => "Weekly fuel allowance of {$rule["amount"]} Liters of {$fuelName} granted to {$employee->lastname}, {$employee->firstname} {$employee->middlename} {$employee->suffix}.",
                        ]);
                    } else if ($type == "2t4t"){
                        ActivityLogger::log([
                            "action" => "granted",
                            "employeeid" => $employee->employeeid,
                            "description" => "Bi Monthly oil allowance of {$rule["amount"]} Liters of {$fuelName} granted to {$employee->lastname}, {$employee->firstname} {$employee->middlename} {$employee->suffix}.",
                        ]);
                    } else if ($type == "b-fluid"){
                        ActivityLogger::log([
                            "action" => "granted",
                            "employeeid" => $employee->employeeid,
                            "description" => "Quarterly fluid allowance of {$rule["amount"]} Liters of {$fuelName} granted to {$employee->lastname}, {$employee->firstname} {$employee->middlename} {$employee->suffix}.",
                        ]);
                    }

    
                    $this->info("✅ Granted {$rule['amount']}L of {$type} to {$employee->firstname} ({$weekStart->toDateString()})");
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();

            throw $e;
        }

    }

    /**
     * Decide if we should create allowance for the given week.
     */
    protected function shouldCreateForWeek(string $frequency, Carbon $weekStart): bool
    {
        switch ($frequency) {
            case 'weekly':
                return true;

            case 'bi-monthly':
                $validMonths = [2, 4, 6, 8, 10, 12];
                return in_array($weekStart->month, $validMonths)
                    && $weekStart->isSameDay(
                        $weekStart->copy()->firstOfMonth(Carbon::MONDAY)
                    );

            case 'quarterly':
                $validMonths = [1, 4, 7, 10];
                return in_array($weekStart->month, $validMonths)
                    && $weekStart->isSameDay(
                        $weekStart->copy()->firstOfMonth(Carbon::MONDAY)
                    );

            default:
                return false;
        }
    }
}
