<?php

namespace App\Http\Controllers\Second;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request){
        $search  = $request->query('search');
        $perPage = $request->query('per_page', 10);
        // $status  = $request->query('status');

        $query = DB::connection('mysql2')
            ->table('employment_setup as es')
            ->leftJoin('employee as e', 'es.employeeid', '=', 'e.employeeid')
            ->where('es.employment_code', function ($q) {
                $q->select(DB::raw('MAX(sub.employment_code)'))
                ->from('employment_setup as sub')
                ->whereColumn('sub.employeeid', 'es.employeeid')
                ->where('sub.isServiceRec', 0);
            })
            ->select('e.*', 'es.activation_status', 'es.employment_code', 'es.dept_code');

        // 🔍 Searching (by employee fields)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('e.employeeid', 'like', "%{$search}%")
                ->orWhere('e.firstname', 'like', "%{$search}%")
                ->orWhere('e.lastname', 'like', "%{$search}%")
                ->orWhere('e.middlename', 'like', "%{$search}%")
                ->orWhere('e.gender', 'like', "%{$search}%")
                ->orWhere('es.dept_code', 'like', "%{$search}%");
            });
        }

        // 📑 Paginate
        $employees = $query->orderBy('e.employeeid', 'desc')->paginate($perPage);

        return response()->json([
            'employees' => $employees,
        ]);
    }
}
