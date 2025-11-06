<?php

namespace App\Services;

use App\Models\Request as ModelsRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestService
{
    public static function isCapableOfRequesting(int $employeeid){
        $fuelRequests = ModelsRequest::where('employeeid', $employeeid)->whereIn('status', ['pending', 'approved'])->get();

        if($fuelRequests->count() > 0){
            throw ValidationException::withMessages([
                'status' => ["This employee has an ongoing request. Please resolve or complete it first."],
            ]);
        }

        return true;
    }
}