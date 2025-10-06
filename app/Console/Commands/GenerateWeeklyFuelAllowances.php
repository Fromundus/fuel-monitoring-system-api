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
        $weekStart = Carbon::now()->startOfWeek()->addWeeks($offsetWeeks);

        $employees = EmployeeService::fetchEmployeeWithBalances()->get();
        $rules = config('fuel_allowances');

        foreach ($employees as $employee) {
            foreach ($rules as $type => $rule) {
                // Skip if already exists for this week + type
                if (FuelAllowance::where('employeeid', $employee->employeeid)
                    ->where('week_start', $weekStart)
                    ->where('type', $type)
                    ->exists()) {
                    continue;
                }

                // Check if we should create based on frequency
                if (! $this->shouldCreateForWeek($rule['frequency'], $weekStart)) {
                    continue;
                }

                // Get last allowance to compute carry-over or advance
                $lastAllowance = FuelAllowance::where('employeeid', $employee->employeeid)
                    ->where('type', $type)
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
                    'allowance'    => $rule['amount'],
                    'carried_over' => $carriedOver,
                    'used'         => 0,
                    'advanced'     => $advanced,
                    'type'         => $type,
                ]);
            }
        }

        $this->info("✅ Fuel allowances generated for week starting: {$weekStart->toDateString()}");
    }

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
                // Fire only on first Monday of Jan, Apr, Jul, Oct
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
