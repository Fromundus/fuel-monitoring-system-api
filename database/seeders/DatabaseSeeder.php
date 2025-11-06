<?php

namespace Database\Seeders;

use App\Models\Barangay;
use App\Models\FuelType;
use App\Models\Inventory;
use App\Models\Purpose;
use App\Models\Setting;
use App\Models\Source;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

        Purpose::factory()->create([
            'name' => "COLLECTION",
        ]);

        Purpose::factory()->create([
            'name' => "KWH METER READING ",
        ]);

        Purpose::factory()->create([
            'name' => "COOP MOTORCYCLE MAINTENANCE",
        ]);
        
        Purpose::factory()->create([
            'name' => "CHANGE OIL",
        ]);

        Setting::factory()->create([
            'key' => 'gasoline-diesel',
            'value' => '8',
            'frequency' => 'weekly',
            'isActive' => true,
        ]);
        
        Setting::factory()->create([
            'key' => '2t4t',
            'value' => '1',
            'frequency' => 'bi-monthly',
            'isActive' => true,
        ]);

        // Setting::factory()->create([
        //     'key' => 'bfluid',
        //     'value' => '1',
        //     'frequency' => 'quarterly',
        //     'isActive' => true,
        // ]);
        
        Setting::factory()->create([
            'key' => 'milestone',
            'value' => '50',
        ]);
        
        Setting::factory()->create([
            'key' => 'liters_per_milestone',
            'value' => '1',
        ]);

        Permission::create(['name' => 'requests_page']);

        Permission::create(['name' => 'employees_page']);

        Permission::create(['name' => 'reports_page']);

        Permission::create(['name' => 'activity_logs_page']);

        Permission::create(['name' => 'accounts_page']);

        Permission::create(['name' => 'source_settings_page']);

        Permission::create(['name' => 'purpose_settings_page']);

        Permission::create(['name' => 'vehicle_settings_page']);

        Permission::create(['name' => 'allowance_settings_page']);

        Permission::create(['name' => 'profile_page']);

        // Create roles
        $superadmin = Role::create(['name' => 'superadmin']);
        $warehouse_admin = Role::create(['name' => 'warehouse_admin']);
        $audit_admin = Role::create(['name' => 'audit_admin']);
        $motorpool_admin = Role::create(['name' => 'motorpool_admin']);

        $user = Role::create(['name' => 'user']);

        // Assign permissions
        $superadmin->givePermissionTo([
            'requests_page', 
            'employees_page',
            'reports_page',
            'activity_logs_page',
            'accounts_page',
            'source_settings_page',
            'purpose_settings_page',
            'vehicle_settings_page',
            'allowance_settings_page',
            'profile_page',
        ]);

        // Assign roles to users
        // $user1->assignRole('admin');

        $this->call([
            BarangaySeeder::class,
            FuelDivisorSeeder::class,
        ]);
    }
}
