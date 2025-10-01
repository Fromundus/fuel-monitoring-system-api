<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request){
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);

        $activityLogs = ActivityLog::orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            "data" => $activityLogs
        ], 200);
    }
}
