<?php

namespace App\Services;

use App\Models\Barangay;
use App\Models\BarangayDistance;
use Illuminate\Support\Facades\DB;

class RouteDistanceService
{
    // public function calculateDistance(array $rows): array
    // {
    //     $rowCount = count($rows);
    //     $result = [];
    //     $totalDistance = 0;

    //     foreach ($rows as $index => $row) {

    //         $departure = $row['departure'] ?? null;
    //         $destination = $row['destination'] ?? null;

    //         if (!$departure || !$destination) {
    //             $result[] = array_merge($row, ['distance' => 0]);
    //             continue;
    //         }

    //         [$fromName, $fromMunicipality] = array_map('trim', explode(',', $departure));
    //         [$toName, $toMunicipality] = array_map('trim', explode(',', $destination));

    //         $from = Barangay::where('name', $fromName)
    //             ->where('municipality', $fromMunicipality)
    //             ->first();

    //         $to = Barangay::where('name', $toName)
    //             ->where('municipality', $toMunicipality)
    //             ->first();

    //         if (!$from || !$to) {
    //             $result[] = array_merge($row, ['distance' => 0]);
    //             continue;
    //         }

    //         $idA = min($from->id, $to->id);
    //         $idB = max($from->id, $to->id);

    //         $distance = BarangayDistance::where('barangay_a_id', $idA)
    //             ->where('barangay_b_id', $idB)
    //             ->first();

    //         if (!$distance) {
    //             $result[] = array_merge($row, ['distance' => 0]);
    //             continue;
    //         }

    //         $additionalRoad = 0;

    //         if ($index !== 0) {
    //             $additionalRoad += $from->road_distance;
    //         }

    //         if ($index !== $rowCount - 1) {
    //             $additionalRoad += $to->road_distance;
    //         }

    //         $distanceKm = ceil(($distance->distance_meters / 1000) + ($additionalRoad * 2));

    //         $totalDistance += $distanceKm;

    //         $result[] = array_merge($row, [
    //             'distance' => $distanceKm,
    //         ]);
    //     }

    //     return [
    //         'rows' => $result,
    //         'total_distance' => $totalDistance,
    //     ];
    // }

    public function calculateDistance(array $rows): array
    {
        $rowCount = count($rows);
        $result = [];
        $totalDistance = 0;

        foreach ($rows as $index => $row) {

            $departure = $row['departure'] ?? null;
            $destination = $row['destination'] ?? null;

            if (!$departure || !$destination) {
                $result[] = array_merge($row, ['distance' => 0]);
                continue;
            }

            [$fromName, $fromMunicipality] = array_map('trim', explode(',', $departure));
            [$toName, $toMunicipality] = array_map('trim', explode(',', $destination));

            $from = Barangay::where('name', $fromName)
                ->where('municipality', $fromMunicipality)
                ->first();

            $to = Barangay::where('name', $toName)
                ->where('municipality', $toMunicipality)
                ->first();

            if (!$from || !$to) {
                $result[] = array_merge($row, ['distance' => 0]);
                continue;
            }

            $isFirstRow = ($index === 0);
            $isLastRow  = ($index === $rowCount - 1);

            /**
             * ---------------------------------------
             * SAME BARANGAY CASE
             * ---------------------------------------
             */
            if ($from->id === $to->id) {

                $additionalRoad = 0;

                if (!$isFirstRow) {
                    $additionalRoad += $from->road_distance;
                }

                if (!$isLastRow) {
                    $additionalRoad += $to->road_distance;
                }

                $distanceKm = ceil($additionalRoad * 2);

                $totalDistance += $distanceKm;

                $result[] = array_merge($row, [
                    'distance' => $distanceKm,
                ]);

                continue;
            }

            /**
             * ---------------------------------------
             * DIFFERENT BARANGAY CASE
             * ---------------------------------------
             */
            $idA = min($from->id, $to->id);
            $idB = max($from->id, $to->id);

            $distance = BarangayDistance::where('barangay_a_id', $idA)
                ->where('barangay_b_id', $idB)
                ->first();

            if (!$distance) {
                $result[] = array_merge($row, ['distance' => 0]);
                continue;
            }

            $additionalRoad = 0;

            if (!$isFirstRow) {
                $additionalRoad += $from->road_distance;
            }

            if (!$isLastRow) {
                $additionalRoad += $to->road_distance;
            }

            $distanceKm = ceil(
                ($distance->distance_meters / 1000) + ($additionalRoad * 2)
            );

            $totalDistance += $distanceKm;

            $result[] = array_merge($row, [
                'distance' => $distanceKm,
            ]);
        }

        return [
            'rows' => $result,
            'total_distance' => $totalDistance,
        ];
    }

    public function calculateDistanceWithoutAdditioal(array $rows): array
    {
        $rowCount = count($rows);
        $result = [];
        $totalDistance = 0;

        foreach ($rows as $index => $row) {

            $departure = $row['departure'] ?? null;
            $destination = $row['destination'] ?? null;

            if (!$departure || !$destination) {
                $result[] = array_merge($row, ['distance' => 0]);
                continue;
            }

            [$fromName, $fromMunicipality] = array_map('trim', explode(',', $departure));
            [$toName, $toMunicipality] = array_map('trim', explode(',', $destination));

            $from = Barangay::where('name', $fromName)
                ->where('municipality', $fromMunicipality)
                ->first();

            $to = Barangay::where('name', $toName)
                ->where('municipality', $toMunicipality)
                ->first();

            if (!$from || !$to) {
                $result[] = array_merge($row, ['distance' => 0]);
                continue;
            }

            $idA = min($from->id, $to->id);
            $idB = max($from->id, $to->id);

            $distance = BarangayDistance::where('barangay_a_id', $idA)
                ->where('barangay_b_id', $idB)
                ->first();

            if (!$distance) {
                $result[] = array_merge($row, ['distance' => 0]);
                continue;
            }

            $distanceKm = ceil(
                ($distance->distance_meters / 1000)
            );

            $totalDistance += $distanceKm;

            $result[] = array_merge($row, [
                'distance' => $distanceKm,
            ]);
        }

        return [
            'rows' => $result,
            'total_distance' => $totalDistance,
        ];
    }
}