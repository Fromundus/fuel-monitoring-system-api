<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmployeeSetting;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EmployeeSettingController extends Controller
{
    public function index()
    {
        $data = EmployeeSetting::with('setting')->get();

        foreach ($data as $item) {
            $item->employee = EmployeeService::fetchActiveEmployee($item->employee_id);
        }

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'setting_id' => 'required|exists:settings,id',
        ]);

        // Verify employee exists in HR system
        $employee = EmployeeService::fetchActiveEmployee($request->employee_id);
        if (! $employee) {
            return response()->json(['error' => 'Employee not found.'], 404);
        }

        $record = EmployeeSetting::create([
            'employee_id' => $request->employee_id,
            'setting_id' => $request->setting_id,
        ]);

        $record->employee = $employee;
        $record->load('setting');

        return response()->json($record, 201);
    }

    public function bulkAssign(Request $request)
    {
        Log::info($request);

        $validated = $request->validate([
            'isAll' => 'sometimes|boolean',
            'setting_ids' => 'required|array|min:1',
            'setting_ids.*' => 'exists:settings,id',
            // 'employee_ids' => 'sometimes|nullable|array|min:1',
            'employee_ids.*' => 'integer',
        ]);

        $created = [];
        $skipped = [];

        if($validated['isAll'] === true){
            $allEmployees = EmployeeService::fetchActiveEmployees()->get();

            foreach($allEmployees as $employee){
                foreach ($validated['setting_ids'] as $settingId) {
                    $exists = EmployeeSetting::where('employee_id', $employee->employeeid)
                        ->where('setting_id', $settingId)
                        ->exists();
    
                    if ($exists) {
                        continue;
                    }
    
                    $record = EmployeeSetting::create([
                        'employee_id' => $employee->employeeid,
                        'setting_id' => $settingId,
                        'active' => true,
                    ]);
    
                }
            }

            return response()->json([
                'message' => 'Bulk assignment complete',
            ]);
        } else {
            foreach ($validated['employee_ids'] as $employeeId) {
                // Verify employee exists in HR DB
                $employee = EmployeeService::fetchActiveEmployee($employeeId);
                if (! $employee) {
                    $skipped[] = [
                        'employee_id' => $employeeId,
                        'reason' => 'Employee not found in HR database',
                    ];
                    continue;
                }
    
                foreach ($validated['setting_ids'] as $settingId) {
                    $exists = EmployeeSetting::where('employee_id', $employeeId)
                        ->where('setting_id', $settingId)
                        ->exists();
    
                    if ($exists) {
                        $skipped[] = [
                            'employee_id' => $employeeId,
                            'setting_id' => $settingId,
                            'reason' => 'Already assigned',
                        ];
                        continue;
                    }
    
                    $record = EmployeeSetting::create([
                        'employee_id' => $employeeId,
                        'setting_id' => $settingId,
                        'active' => true,
                    ]);
    
                    $created[] = $record;
                }
            }
    
            return response()->json([
                'message' => 'Bulk assignment complete',
                'created_count' => count($created),
                'skipped_count' => count($skipped),
                'created' => $created,
                'skipped' => $skipped,
            ]);
        }

    }

    public function destroy(EmployeeSetting $employeeSetting)
    {
        $employeeSetting->delete();
        return response()->json(null, 204);
    }

    public function bulkdelete(Request $request){
        $validated = $request->validate([
            'ids' => 'required|array',
        ]);

        EmployeeSetting::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => 'Employees deleted successfully']);
    }
}
