<?php

use App\Exports\RegisteredMembersExport;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FuelTypeController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\RegisteredMemberController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Second\EmployeeController;
use App\Http\Controllers\Second\SecondController;
use App\Http\Controllers\Second\VehicleController;
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
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::get('/vehicles', [VehicleController::class, 'index']);
        Route::get('/request-data', [SecondController::class, 'requestData']);
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
