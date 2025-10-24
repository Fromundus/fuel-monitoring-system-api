<?php

use App\Exports\RegisteredMembersExport;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BarangayController;
use App\Http\Controllers\Api\BarangayDistanceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EmployeeOverviewController;
use App\Http\Controllers\Api\FuelDivisorController;
use App\Http\Controllers\Api\FuelTypeController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\RegisteredMemberController;
use App\Http\Controllers\Api\RequestController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Second\EmployeeController;
use App\Http\Controllers\Second\SecondController;
use App\Http\Controllers\Second\VehicleController;
use App\Models\Warehousing\Item;
use App\Services\AllowanceService;
use App\Services\EmployeeService;
use App\Services\SettingService;
use App\Services\VehicleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Maatwebsite\Excel\Facades\Excel;

Route::middleware(['auth:sanctum', 'active'])->group(function(){
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('/dashboard')->group(function(){
       Route::get('/admin', [DashboardController::class, 'admin']); 
       Route::get('/user/{id}', [DashboardController::class, 'user']); 
    });
    
    Route::prefix('/inventories')->group(function(){
        Route::get('/', [InventoryController::class, 'index']);
        Route::post('/', [InventoryController::class, 'store']);
        Route::get('/{id}', [InventoryController::class, 'show']);
        Route::put('/', [InventoryController::class, 'update']);
        Route::delete('/', [InventoryController::class, 'destroy']);
    });
    
    //REQUESTS
    Route::prefix('/requests')->group(function(){
        Route::get('/', [RequestController::class, 'index']);
        Route::get('/{id}', [RequestController::class, 'show']);
        Route::post('/', [RequestController::class, 'store']);
        Route::put('/update/{id}', [RequestController::class, 'update']);
        Route::put('/{id}', [RequestController::class, 'updateStatus']);
        Route::delete('/', [RequestController::class, 'delete']);
    });

    //EmployeeOverview  
    Route::get('/employee/overview/{id}', [EmployeeOverviewController::class, 'index']);
    Route::get('/employee/requests/{id}', [EmployeeOverviewController::class, 'employeeRequests']);
    Route::get('/employee/requests/status/{id}', [EmployeeOverviewController::class, 'employeeStatusRequests']);
    Route::get('/employee/activity-logs/{id}', [EmployeeOverviewController::class, 'employeeActivityLogs']);
    
    //ACTIVE EMPLOYEES FROM THE MAIN SERVER
    Route::get('/employeeswithbalance', [EmployeeController::class, 'withFuelBalance']);
    Route::get('/employees', [EmployeeController::class, 'index']);
    Route::get('/employees/{employeeid}', [EmployeeController::class, 'show']);
    Route::get('/vehicles', [VehicleController::class, 'index']);

    Route::post('/set-fuel-divisor', [FuelDivisorController::class, 'update']);

    //BARANGAYS
    // Route::post('/distance', [BarangayDistanceController::class, 'getDistance']);
    Route::post('/distances', [BarangayDistanceController::class, 'getDistances']);

    Route::prefix('/users')->group(function(){
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/', [UserController::class, 'delete']);
    });
    
    Route::middleware('admin')->group(function(){
        
        
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

        Route::post('/scan/request', [RequestController::class, 'scanRequest']);

        //employee and vehicles
        Route::get('/request-data', [SecondController::class, 'requestData']);

        //ACTIVITY LOGS
        Route::get('/activity-logs', [ActivityLogController::class, 'index']);
    });
    
    //USER ACCOUNTS
    Route::put('/updateuser/{id}', [UserController::class, 'update']);
    Route::put('/changepassword/{id}', [UserController::class, 'changePassword']);
});

Route::get('/barangays', [BarangayController::class, 'index']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/test', function(){
    return response()->json([
        "message" => "success"
    ], 200);
});

Route::get('/balance/{employeeid}', function($employeeid){
    $balance = AllowanceService::getBalance($employeeid, 'b-fluid');

    return response()->json([
        "data" => $balance,
        "message" => "success"
    ], 200);
});

Route::get('/test/inventories', [InventoryController::class, 'index']);

Route::get('/test/settings', function(){
    return response()->json([
        "data" => SettingService::getLatestSettings(),
    ]);
});

Route::get('/test/vehicle/{plate_no}', function($plate_no){
    return VehicleService::fetchVehicle($plate_no);
});