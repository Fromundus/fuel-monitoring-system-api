<?php

namespace App\Services;

use App\Models\AllowanceTransaction;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SettingService
{
    public static function getLatestSettings()
    {
        $latestGasolineDiesel = Setting::where('key', 'gasoline-diesel')->latest()->first();
        $latest2t4t = Setting::where('key', '2t4t')->latest()->first();
        $latestBfluid = Setting::where('key', 'bfluid')->latest()->first();
        $latestMilestone = Setting::where('key', 'milestone')->latest()->first();
        $latestLitersPerMilestone = Setting::where('key', 'liters_per_milestone')->latest()->first();

        return [
            "gasoline-diesel" => $latestGasolineDiesel,
            "2t4t" => $latest2t4t,
            "bfluid" => $latestBfluid,
            "milestone" => $latestMilestone,
            "liters_per_milestone" => $latestLitersPerMilestone,
        ];
    }

    public static function getLatestFrequencySettings(){
        $latestGasolineDiesel = Setting::where('key', 'gasoline-diesel')->with('employees')->first();
        $latest2t4t = Setting::where('key', '2t4t')->with('employees')->first();
        // $latestBfluid = Setting::where('key', 'bfluid')->with('employees')->first();

        // Log::info($latestGasolineDiesel);

        return [
            "gasoline-diesel" => $latestGasolineDiesel,
            "2t4t" => $latest2t4t,
            // "bfluid" => $latestBfluid,
        ];
    }

    public static function getLatestMilestoneSettings(){
        $settings = Setting::where('key', 'milestone')->latest()->first();

        return $settings;
    }

    public static function getLatestLitersPerMilestoneSettings(){
        $settings = Setting::where('key', 'liters_per_milestone')->latest()->first();

        return $settings;
    }
}