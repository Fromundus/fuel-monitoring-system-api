<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\FuelType;
use App\Models\Inventory;
use App\Models\Setting;
use App\Models\Source;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'username' => 'user',
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => Hash::make("1234"),
            'role' => 'user',
        ]);

        User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'admin',
        ]);

        User::factory()->create([
            'username' => 'superadmin',
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'superadmin',
        ]);

        Source::factory()->create([
            'name' => 'Ficelco',
        ]);

        Source::factory()->create([
            'name' => 'Silangan Trading',
        ]);

        Source::factory()->create([
            'name' => "GELO'S GO - FUEL",
        ]);

        Setting::factory()->create([
            'key' => 'gasoline-diesel',
            'value' => '8',
            'frequency' => 'weekly',
        ]);
        
        Setting::factory()->create([
            'key' => '2t4t',
            'value' => '1',
            'frequency' => 'bi-monthly',
        ]);

        Setting::factory()->create([
            'key' => 'bfluid',
            'value' => '1',
            'frequency' => 'quarterly',
        ]);
        
        Setting::factory()->create([
            'key' => 'milestone',
            'value' => '50',
        ]);
        
        Setting::factory()->create([
            'key' => 'liters_per_milestone',
            'value' => '1',
        ]);

        // FuelType::factory()->create([
        //     'name' => 'Gasoline',
        //     'unit' => 'Liters',
        //     'unit_short' => 'L',
        // ]);

        // Inventory::factory()->create([
        //     'fuel_type_id' => 1,
        //     'quantity' => 0,
        // ]);

        // FuelType::factory()->create([
        //     'name' => 'Diesel',
        //     'unit' => 'Liters',
        //     'unit_short' => 'L',
        // ]);

        // Inventory::factory()->create([
        //     'fuel_type_id' => 2,
        //     'quantity' => 0,
        // ]);

        // FuelType::factory()->create([
        //     'name' => '4T',
        //     'unit' => 'Liters',
        //     'unit_short' => 'L',
        // ]);

        // Inventory::factory()->create([
        //     'fuel_type_id' => 3,
        //     'quantity' => 0,
        // ]);

        // FuelType::factory()->create([
        //     'name' => '2T',
        //     'unit' => 'Liters',
        //     'unit_short' => 'L',
        // ]);

        // Inventory::factory()->create([
        //     'fuel_type_id' => 4,
        //     'quantity' => 0,
        // ]);

        // FuelType::factory()->create([
        //     'name' => 'B-fluid',
        //     'unit' => 'Liters',
        //     'unit_short' => 'L',
        // ]);

        // Inventory::factory()->create([
        //     'fuel_type_id' => 5,
        //     'quantity' => 0,
        // ]);

        $this->call([
            BarangaySeeder::class,
            FuelDivisorSeeder::class,
        ]);
    }
}
