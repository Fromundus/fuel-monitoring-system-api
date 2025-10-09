<?php

namespace App\Services;

use App\Models\AllowanceTransaction;
use App\Models\Warehousing\Item;
use Carbon\Carbon;

class BalanceWarehouseService
{
    public static function getItemBalance(int $item_id): float
    {
        $item = Item::where('id', $item_id)->first();

        return (float) $item->QuantityOnHand;
    }
}
