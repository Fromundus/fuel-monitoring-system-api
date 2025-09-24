<?php

namespace App\Http\Controllers\Second;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeWithBalanceResource;
use App\Services\EmployeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request){
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);
        // $status  = $request->query('status');

        $query = EmployeeService::fetchActiveEmployees();
        
        // 🔍 Searching (by employee fields)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('e.employeeid', 'like', "%{$search}%")
                ->orWhere('e.firstname', 'like', "%{$search}%")
                ->orWhere('e.lastname', 'like', "%{$search}%")
                ->orWhere('e.middlename', 'like', "%{$search}%")
                ->orWhere('e.gender', 'like', "%{$search}%")
                ->orWhere('es.dept_code', 'like', "%{$search}%")
                ->orWhere('es.emp_status', 'like', "%{$search}%");
            });
        }

        // 📑 Paginate
        $employees = $query->orderBy('e.employeeid', 'desc')->paginate($perPage);

        return response()->json([
            'employees' => [
                'data' => EmployeeWithBalanceResource::collection($employees->items()),
                'current_page' => $employees->currentPage(),
                'last_page'    => $employees->lastPage(),
                'per_page'     => $employees->perPage(),
                'total'        => $employees->total(),
            ]
        ]);
    }

    public function withFuelBalance(Request $request){
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);
        // $status  = $request->query('status');

        $query = EmployeeService::fetchEmployeeWithBalances();

        // 🔍 Searching (by employee fields)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('e.employeeid', 'like', "%{$search}%")
                ->orWhere('e.firstname', 'like', "%{$search}%")
                ->orWhere('e.lastname', 'like', "%{$search}%")
                ->orWhere('e.middlename', 'like', "%{$search}%")
                ->orWhere('e.gender', 'like', "%{$search}%")
                ->orWhere('es.dept_code', 'like', "%{$search}%")
                ->orWhere('es.emp_status', 'like', "%{$search}%");
            });
        }

        // 📑 Paginate
        $employees = $query->orderBy('e.employeeid', 'desc')->paginate($perPage);

        // return response()->json([
        //     'employees' => EmployeeWithBalanceResource::collection($employees),
        // ]);

        return response()->json([
        'employees' => [
            'data' => EmployeeWithBalanceResource::collection($employees->items()),
            'current_page' => $employees->currentPage(),
            'last_page'    => $employees->lastPage(),
            'per_page'     => $employees->perPage(),
            'total'        => $employees->total(),
        ]
    ]);
    }

    public function show($employeeid){
        $employee = EmployeeService::fetchActiveEmployee($employeeid);

        if($employee){
            return response()->json([
                "data" => new EmployeeWithBalanceResource($employee),
            ], 200);
        } else {
            return response()->json(["message" => "Employee Not Found"], 404);
        }
    }
}
