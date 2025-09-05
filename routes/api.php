<?php

use App\Exports\RegisteredMembersExport;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FuelTypeController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\RegisteredMemberController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::middleware(['auth:sanctum', 'active'])->group(function(){
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    
    Route::middleware('admin')->group(function(){
        Route::prefix('/users')->group(function(){
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{id}', [UserController::class, 'show']);
            Route::put('/{id}', [UserController::class, 'update']);
            Route::delete('/', [UserController::class, 'delete']);
        });
        
        Route::put('/update-role', [UserController::class, 'updateRole']);
        Route::put('/update-status', [UserController::class, 'updateStatus']);
        Route::put('/reset-password-default', [UserController::class, 'resetPasswordDefault']);

        Route::prefix('/fuel-types')->group(function(){
            Route::get('/', [FuelTypeController::class, 'index']);
            Route::post('/', [FuelTypeController::class, 'store']);
            Route::get('/{id}', [FuelTypeController::class, 'show']);
            Route::put('/{id}', [FuelTypeController::class, 'update']);
            Route::delete('/', [FuelTypeController::class, 'destroy']);
        });

        Route::prefix('/inventories')->group(function(){
            Route::get('/', [InventoryController::class, 'index']);
            Route::post('/', [InventoryController::class, 'store']);
            Route::get('/{id}', [InventoryController::class, 'show']);
            Route::put('/', [InventoryController::class, 'update']);
            Route::delete('/', [InventoryController::class, 'destroy']);
        });
        
        //ACTIVE EMPLOYEES FROM THE MAIN SERVER
        Route::get('/employees', function(Request $request){
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
                ->select('e.*', 'es.activation_status', 'es.employment_code');

            // 🔍 Searching (by employee fields)
            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('e.employeeid', 'like', "%{$search}%")
                    ->orWhere('e.firstname', 'like', "%{$search}%")
                    ->orWhere('e.lastname', 'like', "%{$search}%")
                    ->orWhere('e.middlename', 'like', "%{$search}%");
                });
            }

            // ✅ Active / Inactive filter
            // if ($status && $status !== 'all') {
            //     if ($status === 'active') {
            //         $query->where('es.activation_status', 'Activate');
            //     } elseif ($status === 'inactive') {
            //         $query->where('es.activation_status', '!=', 'Activate');
            //     }
            // }

            // 📑 Paginate
            $employees = $query->orderBy('e.employeeid', 'desc')->paginate($perPage);

            // 📊 Counts
            $roleCounts = [
                'total'   => DB::connection('mysql2')->table('employee')->count(),
                'active'  => DB::connection('mysql2')
                                ->table('employment_setup')
                                ->where('activation_status', 'Activate')
                                ->distinct('employeeid')
                                ->count('employeeid'),
                'inactive'=> DB::connection('mysql2')
                                ->table('employment_setup')
                                ->where('activation_status', '!=', 'Activate')
                                ->distinct('employeeid')
                                ->count('employeeid'),
            ];

            return response()->json([
                'employees' => $employees,
                'counts'    => $roleCounts,
            ]);
        });
    });
    
    //USER ACCOUNTS
    Route::put('/updateuser/{id}', [UserController::class, 'update']);
    Route::put('/changepassword/{id}', [UserController::class, 'changePassword']);
});

Route::post('/login', [AuthController::class, 'login']);

Route::get('/test', function(){
    return response()->json([
        "message" => "success"
    ], 200);
});
