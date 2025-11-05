<?php

namespace App\Services;

use App\Models\AllowanceTransaction;
use App\Models\Request as ModelsRequest;
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

    // public static function calculateMilestone(int $employeeid)
    // {
    //     $milestone = SettingService::getLatestMilestoneSettings()->value;
    //     $litersPerMilestone = SettingService::getLatestLitersPerMilestoneSettings()->value;

    //     Log::info($milestone);

    //     $totalDistance = EmployeeService::getTotalDistanceTravelled($employeeid); // released trips only

    //     $expectedMilestones = floor($totalDistance / $milestone);
    //     $expectedLiters = $expectedMilestones * $litersPerMilestone;

    //     Log::info("Expected: {$expectedLiters}");

    //     $grantedLiters = AllowanceTransaction::where('employeeid', $employeeid)
    //         ->where('type', 'trip-ticket')
    //         ->whereIn('tx_type', ['grant', 'adjustment'])
    //         ->sum('quantity');

    //     Log::info("Granted: {$grantedLiters}");

    //     $difference = $expectedLiters - $grantedLiters;

    //     if ($difference != 0) {
    //         AllowanceTransaction::create([
    //             'employeeid' => $employeeid,
    //             'type'       => 'trip-ticket',
    //             'tx_type'    => $difference > 0 ? 'grant' : 'adjustment',
    //             'quantity'   => $difference,
    //             'reference'  => "system:trip-ticket:".now()->toDateTimeString(),
    //             'granted_at' => now(),
    //         ]);
    //     }
    // }

    public static function calculateMilestone(int $employeeid)
    {
        // load only released fuel requests and their tickets/rows
        $fuelRequests = ModelsRequest::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')
            ->where('status', 'released')
            ->with(['tripTickets.rows'])
            ->get();

        // total expected liters according to snapshots
        $totalExpectedLiters = 0;

        foreach ($fuelRequests as $request) {
            foreach ($request->tripTickets as $ticket) {
                // Fallback: if snapshot missing, fall back to latest settings
                $milestone = $ticket->milestone_value ?? SettingService::getLatestMilestoneSettings()->value;
                $litersPerMilestone = $ticket->liters_per_milestone ?? SettingService::getLatestLitersPerMilestoneSettings()->value;

                // If milestone is zero or null: skip to avoid division by zero
                if (!$milestone || $milestone <= 0) {
                    continue;
                }

                foreach ($ticket->rows as $row) {
                    $distance = (float) ($row->distance ?? 0);
                    $expectedMilestones = floor($distance / $milestone);
                    $totalExpectedLiters += $expectedMilestones * (float) $litersPerMilestone;
                }
            }
        }

        // granted liters (already given through the system)
        $grantedLiters = AllowanceTransaction::where('employeeid', $employeeid)
            ->where('type', 'trip-ticket')
            ->whereIn('tx_type', ['grant', 'adjustment'])
            ->sum('quantity');

        $difference = $totalExpectedLiters - $grantedLiters;

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

        return [
            'expected' => $totalExpectedLiters,
            'granted'  => $grantedLiters,
            'difference' => $difference,
        ];
    }

}
