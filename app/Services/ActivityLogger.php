<?php

// app/Services/ActivityLogger.php
namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(array $data): ActivityLog
    {
        $request = $data["request"];

        $description = '';

        $employee = EmployeeService::fetchActiveEmployee(employeeid: $request->employeeid); 

        // $employee->firstname . $employee->middlename . ' ' . $employee->lastname . ' ' . $employee->suffix

        if($data['action'] === "created"){
            $description = auth()->user()->name . ' created a new request with reference number ' . $request->reference_number . '.';
        } else if ($data['action'] === "approved"){
            $description = auth()->user()->name . ' approved the request ' . $request->reference_number . '.';
        } else if ($data['action'] === "rejected"){
            $description = auth()->user()->name . ' rejected the request ' . $request->reference_number . '.';
        } else if ($data['action'] === "cancelled"){
            $description = auth()->user()->name . ' cancelled the request ' . $request->reference_number . '.';
        } else if ($data['action'] === "released"){
            $description = 'Request ' . $request->reference_number . ' was released by ' . auth()->user()->name . ' — ' . $request->quantity . ' ' . $request->unit . ' ' . $request->fuel_type . '.';
        }

        return ActivityLog::create([
            'request_id'       => $request->id,
            'user_id'          => auth()->id() ?? null,
            'employee_id'      => $request->employeeid ?? null,
            'action'           => $data['action'],
            'description'      => $description ?? null,
            'item_id'          => $request->fuel_type_id ?? null,
            'item_name'        => $request->fuel_type ?? null,
            'item_unit'        => $request->unit ?? null,
            'quantity'         => $request->quantity ?? null,
            'reference_number' => $request->reference_number ?? null,
            'reference_type'   => 'FR',
        ]);
    }
}
