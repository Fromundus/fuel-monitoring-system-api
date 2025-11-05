<?php

namespace App\Services;

use App\Models\AllowanceTransaction;
use App\Models\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class MilestoneAllowanceService
{
    public static function releaseFuel(int $employeeid, float $liters, string $ref)
    {
        AllowanceTransaction::create([
            'employeeid' => $employeeid,
            'type'       => 'trip-ticket',
            'tx_type'    => 'use',
            'quantity'   => -$liters,
            'reference'  => $ref,
        ]);
    }

    public static function undoFuelRelease(int $employeeid, string $ref)
    {
        $useTx = AllowanceTransaction::where('employeeid', $employeeid)
            ->where('reference', $ref)
            ->where('tx_type', 'use')
            ->first();

        if ($useTx) {
            AllowanceTransaction::create([
                'employeeid' => $employeeid,
                'type'       => $useTx->type,
                'tx_type'    => 'reversal',
                'quantity'   => abs($useTx->quantity),
                'reference'  => "undo:{$ref}",
            ]);
        }

        self::calculateMilestone($employeeid);
    }

    public static function calculateMilestone(int $employeeid)
    {
        $milestone = SettingService::getLatestMilestoneSettings()->value;
        $litersPerMilestone = SettingService::getLatestLitersPerMilestoneSettings()->value;

        Log::info($milestone);

        $totalDistance = EmployeeService::getTotalDistanceTravelled($employeeid); // released trips only

        $expectedMilestones = floor($totalDistance / $milestone);
        $expectedLiters = $expectedMilestones * $litersPerMilestone;

        Log::info("Expected: {$expectedLiters}");

        $grantedLiters = AllowanceTransaction::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')
            ->whereIn('tx_type', ['grant', 'adjustment'])
            ->sum('quantity');

        Log::info("Granted: {$grantedLiters}");

        $difference = $expectedLiters - $grantedLiters;

        if ($difference != 0) {
            AllowanceTransaction::create([
                'employeeid' => $employeeid,
                'type'       => 'trip-ticket',
                'tx_type'    => $difference > 0 ? 'grant' : 'adjustment',
                'quantity'   => $difference,
                'reference'  => "system:trip-ticket:".now()->toDateTimeString(),
                'granted_at' => now(),
            ]);
        }
    }
}
