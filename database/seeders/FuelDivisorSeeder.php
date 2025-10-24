<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FuelDivisorSeeder extends Seeder
{
    public function run(): void
    {
        // Set your default divisor value here
        $defaultKmDivisor = 1; // e.g. 10 km per liter

        // Fetch all vehicles from the secondary database
        $vehicles = DB::connection('mysql2')
            ->table('vehicles as v')
            ->select('v.id')
            ->join(
                DB::raw('(SELECT plate_no, MAX(id) as latest_id FROM vehicles GROUP BY plate_no) as latest'),
                function ($join) {
                    $join->on('v.id', '=', 'latest.latest_id');
                }
            )
            ->get();

        $count = 0;

        foreach ($vehicles as $vehicle) {
            // Create only if not existing
            $exists = DB::connection('mysql')
                ->table('fuel_divisors')
                ->where('vehicle_id', $vehicle->id)
                ->exists();

            if (!$exists) {
                DB::connection('mysql')
                    ->table('fuel_divisors')
                    ->insert([
                        'vehicle_id' => $vehicle->id,
                        'km_divisor' => $defaultKmDivisor,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                $count++;
            }
        }

        $this->command->info("✅ Fuel divisors seeded successfully for {$count} vehicles.");
    }
}
