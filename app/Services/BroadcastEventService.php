<?php

namespace App\Services;

use App\Events\BroadcastEvent;
use App\Models\FuelDivisor;
use Illuminate\Support\Facades\DB;

class BroadcastEventService
{
    public static function signal(string $signal, int | null $id = null)
    {
        broadcast(new BroadcastEvent(["signal" => $signal, "id" => $id]));
    }
}