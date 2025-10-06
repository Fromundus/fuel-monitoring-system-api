<?php

// app/Services/ActivityLogger.php
namespace App\Services;

use App\Models\ActivityLog;

class ActivityLogger
{
    public static function log(array $data): ActivityLog
    {
        $request = $data["request"] ?? null;

        $requestBeforeUpdate = $data["requestBeforeUpdate"] ?? null;

        $description = null;

        $employeeid = $request->employeeid ?? $data["employeeid"];

        $employee = EmployeeService::fetchActiveEmployee(employeeid: $employeeid); 

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
        } else if ($data['action'] === "undo"){
            if($requestBeforeUpdate["status"] === "released"){
                $description = auth()->user()->name . " undid the release of {$request->quantity} {$request->unit} {$request->fuel_type} for request {$request->reference_number}. Inventory restored.";
            } else if($requestBeforeUpdate["status"] === "approved"){
                $description = "Approval for request {$request->reference_number} was revoked by " . auth()->user()->name . ". Status set back to pending.";
            } else if($requestBeforeUpdate["status"] === "rejected"){
                $description = "Rejection for request {$request->reference_number} was revoked by " . auth()->user()->name . ". Status set back to pending.";
            } else if($requestBeforeUpdate["status"] === "cancelled"){
                $description = "Cancellation for request {$request->reference_number} was revoked by " . auth()->user()->name . ". Status set back to pending.";
            }
        } else if ($data['action'] === "update") {
            $before = $data['before'] ?? [];
            $after  = $data['after'] ?? [];

            $changes = [];

            // Fields you want to ignore in comparison
            $ignoredFields = [
                'updated_at',
                'created_at',
                'reference_number',
                'id',
                'date'
            ];

            foreach ($after as $field => $newValue) {
                if (in_array($field, $ignoredFields)) {
                    continue; // skip system fields
                }

                $oldValue = $before[$field] ?? null;

                // Compare values only if they differ (strict enough to avoid type issues)
                if ($oldValue != $newValue) {
                    $changes[] = ucfirst(str_replace('_', ' ', $field)) .
                        " changed from '{$oldValue}' to '{$newValue}'";
                }
            }

            if (count($changes) > 0) {
                $description = auth()->user()->name .
                    ' updated request ' . $request->reference_number . ' — ' .
                    implode('; ', $changes) . '.';
            } else {
                $description = auth()->user()->name .
                    ' updated request ' . $request->reference_number . ' (no major changes detected).';
            }
        }

        //update action

        return ActivityLog::create([
            'request_id'       => $request->id ?? null,
            'user_id'          => auth()->id() ?? null,
            'employee_id'      => $data["employeeid"] ?? $request->employeeid ?? null,
            'action'           => $data['action'],
            'description'      => $data["description"] ?? $description ?? null,
            'item_id'          => $data["item_id"] ?? $request->fuel_type_id ?? null,
            'item_name'        => $data["item_name"] ?? $request->fuel_type ?? null,
            'item_unit'        => $data["item_unit"] ?? $request->unit ?? null,
            'quantity'         => $data["quantity"] ?? $request->quantity ?? null,
            'reference_number' => $data["reference_number"] ?? $request->reference_number ?? null,
            'reference_type'   => 'FR',
        ]);
    }
}
