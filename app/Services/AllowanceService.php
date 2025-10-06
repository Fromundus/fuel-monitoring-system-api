<?php

namespace App\Services;

use App\Models\AllowanceTransaction;
use Carbon\Carbon;

class AllowanceService
{
    /**
     * Record usage by (quantity will be stored as negative).
     */
    public static function use(int $employeeid, string $type, float $quantity, ?string $reference = null): void
    {
        AllowanceTransaction::create([
            'employeeid' => $employeeid,
            'type'       => $type,
            'tx_type'    => 'use',
            'quantity'   => -$quantity,
            'reference'  => $reference,
            'granted_at' => Carbon::now(),
        ]);
    }
    

    public static function undo(int $employeeid, string $type, float $quantity, ?string $reference = null): void
    {
        AllowanceTransaction::create([
            'employeeid' => $employeeid,
            'type'       => $type,
            'tx_type'    => 'adjustment',
            'quantity'   => $quantity,
            'reference'  => $reference,
            'granted_at' => Carbon::now(),
        ]);
    }

    public static function getLatestGrantedBalanceRow(int $employeeid, string $type){
        return AllowanceTransaction::where('employeeid', operator: $employeeid)->where('type', $type)->orderByDesc('id')->first();
    }

    /**
     * Get balance = sum of all transactions.
     */
    public static function getBalance(int $employeeid, string $type): float
    {
        return (float) AllowanceTransaction::where('employeeid', $employeeid)
            ->where('type', $type)
            ->sum('quantity');
    }
}
