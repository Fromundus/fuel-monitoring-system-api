<?php

namespace Database\Seeders;

use App\Models\Barangay;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BarangaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->command->info('Seeding barangays...');
        $jsonPath = database_path('seeders/data/barangays.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Barangay::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($data as $row) {
            Barangay::create([
                'name' => $row['name'],
                'lat' => $row['lat'],
                'lng' => $row['lng'],
                'municipality' => $row['municipality'] ?? null,
            ]);
        }

        $this->command->info('Inserted ' . Barangay::count() . ' barangays.');
    }
}
