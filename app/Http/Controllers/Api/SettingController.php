<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index(){
        $allowances = Setting::with("employees")->get();
        return $allowances;
    }

    public function show($id){
        $setting = Setting::with("employees")->findOrFail($id);

        return $setting;
    }

    public function toggleActiveStatus($id){
        $allowance = Setting::findOrFail($id);

        $allowance->update([
            "isActive" => !$allowance["isActive"],
        ]);

        return response()->json([
            "status" => true
        ], 200);
    }

    public function update(Request $request, $id){
        $validated = $request->validate([
            "frequency" => "sometimes|nullable|string",
            "value" => "sometimes|numeric",
        ]);

        $allowance = Setting::findOrFail($id);

        $allowance->update([
            "frequency" => $validated["frequency"],
            "value" => $validated["value"],
        ]);

        return response()->json([
            "status" => true
        ], 200);
    }
}
