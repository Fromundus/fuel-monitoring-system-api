<?php

namespace App\Http\Controllers\Tets;

use App\Events\BroadcastEvent;
use App\Events\TestEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BroadcastTestController extends Controller
{
    public function broadcast(Request $request)
    {
        broadcast(new BroadcastEvent(["signal" => "request"]));

        return response()->json(['status' => 'broadcasted']);
    }
}