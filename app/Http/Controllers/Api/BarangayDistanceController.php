<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Barangay;
use App\Models\BarangayDistance;
use App\Services\RouteDistanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BarangayDistanceController extends Controller
{
    public function __construct(
        protected RouteDistanceService $distanceService
    ) {}
    
    public function getDistance(Request $request)
    {
        $fromId = $request->from_id;
        $toId   = $request->to_id;

        if ($fromId && $toId) {
            // Lookup by IDs
            $from = Barangay::find($fromId);
            $to   = Barangay::find($toId);
        } else {
            // Fallback: lookup by name + municipality
            $fromName = $request->from_name;
            $fromMunicipality = $request->from_municipality;
            $toName   = $request->to_name;
            $toMunicipality = $request->to_municipality;

            if (!$fromName || !$toName || !$fromMunicipality || !$toMunicipality) {
                return response()->json(['error' => 'Missing parameters. Use from_id/to_id or name+municipality'], 400);
            }

            $from = Barangay::where('name', $fromName)
                            ->where('municipality', $fromMunicipality)
                            ->first();

            $to = Barangay::where('name', $toName)
                          ->where('municipality', $toMunicipality)
                          ->first();
        }

        if (!$from || !$to) {
            return response()->json(['error' => 'Barangay not found'], 404);
        }

        // Ensure a_id < b_id for lookup
        $idA = min($from->id, $to->id);
        $idB = max($from->id, $to->id);

        $distance = BarangayDistance::where('barangay_a_id', $idA)
                                    ->where('barangay_b_id', $idB)
                                    ->first();

        if (!$distance) {
            return response()->json(['error' => 'Distance not calculated'], 404);
        }

        return response()->json([
            'from' => [
                'id' => $from->id,
                'name' => $from->name,
                'municipality' => $from->municipality,
            ],
            'to' => [
                'id' => $to->id,
                'name' => $to->name,
                'municipality' => $to->municipality,
            ],
            'distance_meters' => $distance->distance_meters,
            'distance' => ceil($distance->distance_meters / 1000),
            'time_ms' => $distance->time_ms,
            'route' => $distance->route_raw,

            // 'quantity' => number_format(ceil($distance->distance_meters / 1000) / 35, 2),
            'quantity' => number_format(ceil(ceil($distance->distance_meters / 1000) / 35), 2),
        ]);
    }

    // public function getDistances(Request $request)
    // {
    //     $rows = $request->input('rows'); // expect array of tripTicketRows
    //     $fuel_divisor = $request->input('fuel_divisor');

    //     if (!$rows || !is_array($rows)) {
    //         return response()->json(['error' => 'Rows array is required'], 400);
    //     }

    //     $result = [];
    //     $exactTotalDistance = 0;
    //     $totalDistance = 0;
    //     $totalQuantity = 0;

    //     foreach ($rows as $row) {
    //         $departure = $row['departure'] ?? null;
    //         $destination = $row['destination'] ?? null;

    //         if (!$departure || !$destination) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //             ]);
    //             continue;
    //         }

    //         // split "Barangay, Municipality"
    //         [$fromName, $fromMunicipality] = array_map('trim', explode(',', $departure));
    //         [$toName, $toMunicipality] = array_map('trim', explode(',', $destination));

    //         $from = Barangay::where('name', $fromName)
    //                         ->where('municipality', $fromMunicipality)
    //                         ->first();

    //         $to = Barangay::where('name', $toName)
    //                     ->where('municipality', $toMunicipality)
    //                     ->first();

    //         if (!$from || !$to) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //                 'error' => 'Barangay not found',
    //             ]);
    //             continue;
    //         }

    //         $idA = min($from->id, $to->id);
    //         $idB = max($from->id, $to->id);

    //         $distance = BarangayDistance::where('barangay_a_id', $idA)
    //                                     ->where('barangay_b_id', $idB)
    //                                     ->first();

    //         if (!$distance) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //                 'error' => 'Distance not calculated',
    //             ]);
    //             continue;
    //         }

    //         $exactDistance = $distance->distance_meters / 1000;
    //         // $distanceKm = ceil(($distance->distance_meters / 1000) + (($to->road_distance + $from->road_distance) * 2));
    //         $distanceKm = ceil(($distance->distance_meters / 1000) + (($to->road_distance + $from->road_distance)));

    //         // $quantity = ceil($distanceKm / 35);
    //         $quantity = $distanceKm / $fuel_divisor;

    //         // add to totals
    //         $exactTotalDistance += $exactDistance;
    //         $totalDistance += $distanceKm;
    //         $totalQuantity += $quantity;

    //         $result[] = array_merge($row, [
    //             'distance' => $distanceKm,
    //             'quantity' => number_format($quantity, decimals: 2),
    //         ]);
    //     }

    //     return response()->json([
    //         'rows' => $result,
    //         // 'exact_total_distance' => number_format($exactTotalDistance, 2),
    //         'total_distance' => number_format($totalDistance, 2),
    //         'total_quantity' => number_format($totalQuantity, 2),
    //         'fuel_divisor' => number_format( $fuel_divisor, 2),
    //     ]);
    // }

    // public function getDistances(Request $request)
    // {
    //     $rows = $request->input('rows'); 
    //     $fuel_divisor = $request->input('fuel_divisor');
    //     $purpose = $request->input('purpose');

    //     if (!$rows || !is_array($rows)) {
    //         return response()->json(['error' => 'Rows array is required'], 400);
    //     }

    //     $result = [];
    //     $exactTotalDistance = 0;
    //     $totalDistance = 0;
    //     $totalQuantity = 0;

    //     $rowCount = count($rows);

    //     foreach ($rows as $index => $row) {

    //         $departure = $row['departure'] ?? null;
    //         $destination = $row['destination'] ?? null;

    //         if (!$departure || !$destination) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //             ]);
    //             continue;
    //         }

    //         [$fromName, $fromMunicipality] = array_map('trim', explode(',', $departure));
    //         [$toName, $toMunicipality] = array_map('trim', explode(',', $destination));

    //         $from = Barangay::where('name', $fromName)
    //                         ->where('municipality', $fromMunicipality)
    //                         ->first();

    //         $to = Barangay::where('name', $toName)
    //                     ->where('municipality', $toMunicipality)
    //                     ->first();

    //         if (!$from || !$to) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //                 'error' => 'Barangay not found',
    //             ]);
    //             continue;
    //         }

    //         $idA = min($from->id, $to->id);
    //         $idB = max($from->id, $to->id);

    //         $distance = BarangayDistance::where('barangay_a_id', $idA)
    //                                     ->where('barangay_b_id', $idB)
    //                                     ->first();

    //         if (!$distance) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //                 'error' => 'Distance not calculated',
    //             ]);
    //             continue;
    //         }

    //         $exactDistance = $distance->distance_meters / 1000;

    //         // -----------------------------------------------
    //         // APPLY ROAD DISTANCE ONLY IF NOT FIRST FROM OR LAST TO
    //         // -----------------------------------------------
    //         $additionalRoad = 0;

    //         $isFirstRow = ($index === 0);
    //         $isLastRow = ($index === $rowCount - 1);

    //         if (!$isFirstRow) {
    //             $additionalRoad += $from->road_distance;
    //         }

    //         if (!$isLastRow) {
    //             $additionalRoad += $to->road_distance;
    //         }

    //         $distanceKm = ceil(($distance->distance_meters / 1000) + ($additionalRoad * 2));

    //         $distanceFromAtoB = ceil($distance->distance_meters / 1000);

    //         $quantity = $distanceKm / $fuel_divisor;

    //         // totals
    //         $exactTotalDistance += $exactDistance;
    //         $totalDistance += $distanceKm;
    //         $totalQuantity += $quantity;

    //         $result[] = array_merge($row, [
    //             'distance' => $distanceKm,
    //             'quantity' => number_format($quantity, 2),
    //             'from_road_distance' => $from->road_distance,
    //             'to_road_distance' => $to->road_distance,
    //             'additional_road_distance' => $additionalRoad,
    //             'distance_from_a_to_b' => $distanceFromAtoB,
    //         ]);
    //     }

    //     return response()->json([
    //         'rows' => $result,
    //         'total_distance' => number_format($totalDistance, 2),
    //         'total_quantity' => number_format($totalQuantity, 2),
    //         'fuel_divisor' => number_format($fuel_divisor, 2),
    //     ]);
    // }

    // public function getDistances(Request $request)
    // {
    //     $rows = $request->input('rows');
    //     $fuel_divisor = $request->input('fuel_divisor');
    //     $purpose = $request->input('purpose');

    //     Log::info($purpose);

    //     if (!$rows || !is_array($rows)) {
    //         return response()->json(['error' => 'Rows array is required'], 400);
    //     }

    //     $result = [];
    //     $exactTotalDistance = 0;
    //     $totalDistance = 0;
    //     $totalQuantity = 0;

    //     $rowCount = count($rows);

    //     foreach ($rows as $index => $row) {

    //         $departure = $row['departure'] ?? null;
    //         $destination = $row['destination'] ?? null;

    //         if (!$departure || !$destination) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //             ]);
    //             continue;
    //         }

    //         [$fromName, $fromMunicipality] = array_map('trim', explode(',', $departure));
    //         [$toName, $toMunicipality] = array_map('trim', explode(',', $destination));

    //         $from = Barangay::where('name', $fromName)
    //                         ->where('municipality', $fromMunicipality)
    //                         ->first();

    //         $to = Barangay::where('name', $toName)
    //                     ->where('municipality', $toMunicipality)
    //                     ->first();

    //         if (!$from || !$to) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //                 'error' => 'Barangay not found',
    //             ]);
    //             continue;
    //         }

    //         $isFirstRow = ($index === 0);
    //         $isLastRow  = ($index === $rowCount - 1);

    //         /**
    //          * ---------------------------------------------------
    //          * SAME BARANGAY CASE (NO A → B DISTANCE)
    //          * ---------------------------------------------------
    //          */
    //         if ($from->id === $to->id) {

    //             $additionalRoad = 0;

    //             if (!$isFirstRow) {
    //                 $additionalRoad += $from->road_distance;
    //             }

    //             if (!$isLastRow) {
    //                 $additionalRoad += $to->road_distance;
    //             }

    //             $distanceKm = ceil($additionalRoad * 2);
    //             $quantity = $fuel_divisor > 0 ? $distanceKm / $fuel_divisor : 0;

    //             $totalDistance += $distanceKm;
    //             $totalQuantity += $quantity;

    //             $result[] = array_merge($row, [
    //                 'distance' => $distanceKm,
    //                 'quantity' => number_format($quantity, 2),
    //                 'from_road_distance' => $from->road_distance,
    //                 'to_road_distance' => $to->road_distance,
    //                 'additional_road_distance' => $additionalRoad,
    //                 'distance_from_a_to_b' => 0,
    //             ]);

    //             continue;
    //         }

    //         /**
    //          * ---------------------------------------------------
    //          * DIFFERENT BARANGAY CASE
    //          * ---------------------------------------------------
    //          */
    //         $idA = min($from->id, $to->id);
    //         $idB = max($from->id, $to->id);

    //         $distance = BarangayDistance::where('barangay_a_id', $idA)
    //                                     ->where('barangay_b_id', $idB)
    //                                     ->first();

    //         if (!$distance) {
    //             $result[] = array_merge($row, [
    //                 'distance' => 0,
    //                 'quantity' => 0,
    //                 'error' => 'Distance not calculated',
    //             ]);
    //             continue;
    //         }

    //         $exactDistance = $distance->distance_meters / 1000;

    //         $additionalRoad = 0;

    //         if (!$isFirstRow) {
    //             $additionalRoad += $from->road_distance;
    //         }

    //         if (!$isLastRow) {
    //             $additionalRoad += $to->road_distance;
    //         }

    //         $distanceFromAtoB = ceil($distance->distance_meters / 1000);
    //         $distanceKm = ceil(($distance->distance_meters / 1000) + ($additionalRoad * 2));

    //         $quantity = $distanceKm / $fuel_divisor;

    //         // totals
    //         $exactTotalDistance += $exactDistance;
    //         $totalDistance += $distanceKm;
    //         $totalQuantity += $quantity;

    //         $result[] = array_merge($row, [
    //             'distance' => $distanceKm,
    //             'quantity' => number_format($quantity, 2),
    //             'from_road_distance' => $from->road_distance,
    //             'to_road_distance' => $to->road_distance,
    //             'additional_road_distance' => $additionalRoad,
    //             'distance_from_a_to_b' => $distanceFromAtoB,
    //         ]);
    //     }

    //     return response()->json([
    //         'rows' => $result,
    //         'total_distance' => number_format($totalDistance, 2),
    //         'total_quantity' => number_format($totalQuantity, 2),
    //         'fuel_divisor' => number_format($fuel_divisor, 2),
    //     ]);
    // }

    public function getDistances(Request $request)
    {
        $rows = $request->input('rows');
        $fuel_divisor = (float) $request->input('fuel_divisor', 0);
        $purpose = $request->input('purpose');

        if (!$rows || !is_array($rows)) {
            return response()->json(['error' => 'Rows array is required'], 400);
        }

        if($purpose && $purpose === "METER READING"){
            $distanceResult = $this->distanceService->calculateDistance($rows);
        } else {
            $distanceResult = $this->distanceService->calculateDistanceWithoutAdditioal($rows);
        }


        $result = [];
        $totalQuantity = 0;

        foreach ($distanceResult['rows'] as $row) {
            $distanceKm = $row['distance'] ?? 0;

            $quantity = ($fuel_divisor > 0)
                ? $distanceKm / $fuel_divisor
                : 0;

            $totalQuantity += $quantity;

            $result[] = array_merge($row, [
                'quantity' => number_format($quantity, 2),
            ]);
        }

        return response()->json([
            'rows' => $result,
            'total_distance' => number_format($distanceResult['total_distance'], 2),
            'total_quantity' => number_format($totalQuantity, 2),
            'fuel_divisor' => number_format($fuel_divisor, 2),
        ]);
    }
}
