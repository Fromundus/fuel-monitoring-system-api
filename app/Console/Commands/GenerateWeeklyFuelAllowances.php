<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FuelAllowance;
use App\Services\EmployeeService;
use Carbon\Carbon;

class GenerateWeeklyFuelAllowances extends Command
{
    // Add an optional --weeks argument
    protected $signature = 'fuel:generate-allowances {--weeks=0 : Offset weeks from the current week}';
    protected $description = 'Generate weekly fuel allowances for all employees';

    public function handle()
    {
        $offsetWeeks = (int) $this->option('weeks');

        // Simulated week start: current Monday + offset
        $weekStart = Carbon::now()->startOfWeek()->addWeeks($offsetWeeks);

        $employees = EmployeeService::fetchEmployeeWithBalances()->get();

        foreach ($employees as $employee) {
            // Skip if this week's allowance already exists
            if (FuelAllowance::where('employeeid', $employee->employeeid)
                ->where('week_start', $weekStart)
                ->exists()) {
                continue;
            }

            // Get last allowance to determine carry-over/advance
            $lastAllowance = FuelAllowance::where('employeeid', $employee->employeeid)
                ->orderByDesc('week_start')
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
                'employeeid'   => $employee->employeeid,
                'week_start'   => $weekStart,
                'allowance'    => 8.00,
                'carried_over' => $carriedOver,
                'used'         => 0,
                'advanced'     => $advanced,
            ]);
        }

        $this->info("✅ Weekly fuel allowances generated for simulated week starting: {$weekStart->toDateString()}");
    }
}
