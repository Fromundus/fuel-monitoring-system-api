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
        //REQUESTS
        Permission::create(['name' => 'requests_page']);
            Permission::create(['name' => 'requests_outside_requests']);
            Permission::create(['name' => 'requests_outside_requests_bulk_release']);
            Permission::create(['name' => 'requests_scanner']);
            Permission::create(['name' => 'requests_add']);
            Permission::create(['name' => 'requests_generate_report']);
            Permission::create(['name' => 'requests_details']);
            Permission::create(['name' => 'requests_edit']);
            Permission::create(['name' => 'requests_update_status_approve']);
            Permission::create(['name' => 'requests_update_status_reject']);
            Permission::create(['name' => 'requests_update_status_cancel']);
            Permission::create(['name' => 'requests_update_status_release']);
            // Permission::create(['name' => 'requests_update_status_undo']);
            Permission::create(['name' => 'requests_update_status_undo_approve']);
            Permission::create(['name' => 'requests_update_status_undo_reject']);
            Permission::create(['name' => 'requests_update_status_undo_cancel']);
            Permission::create(['name' => 'requests_update_status_undo_release']);

        //EMPLOYEES
        Permission::create(['name' => 'employees_page']);
            Permission::create(['name' => 'employees_details']);

        //REPORTS
        // Permission::create(['name' => 'reports_page']);

        //ACTIVITY LOGS
        Permission::create(['name' => 'activity_logs_page']);

        //ACCOUNTS
        Permission::create(['name' => 'accounts_page']);
            Permission::create(['name' => 'accounts_add']);
            // Permission::create(['name' => 'accounts_bulk_update_role']);
            // Permission::create(['name' => 'accounts_update_role']);
            Permission::create(['name' => 'accounts_manage_roles_and_permissions']);
            Permission::create(['name' => 'accounts_bulk_delete_user']);
            Permission::create(['name' => 'accounts_details']);
            Permission::create(['name' => 'accounts_details_update']);
            Permission::create(['name' => 'accounts_update_status']); // deactivate / activate
            Permission::create(['name' => 'accounts_reset_password']);
        //to be continued

        //SOURCE SETTINGS
        Permission::create(['name' => 'source_settings_page']);
            Permission::create(['name' => 'source_settings_add']);
            Permission::create(['name' => 'source_settings_update']);

        //PURPOSE SETTINGS
        Permission::create(['name' => 'purpose_settings_page']);
            Permission::create(['name' => 'purpose_settings_add']);
            Permission::create(['name' => 'purpose_settings_update']);

        //VEHICLE SETTINGS
        Permission::create(['name' => 'vehicle_settings_page']);
            Permission::create(['name' => 'vehicle_settings_set_divisor']);

        //ALLOWANCE SETTINGS
        Permission::create(['name' => 'allowance_settings_page']);
            Permission::create(['name' => 'allowance_settings_view_list']);
            Permission::create(['name' => 'allowance_settings_view_list_remove']);
            Permission::create(['name' => 'allowance_settings_assign_employees']);
            Permission::create(['name' => 'allowance_settings_update_values']);

        //ROUTE SETTINGS
        Permission::create(['name' => 'route_settings_page']);
        
        //PROFILE PAGE
        Permission::create(['name' => 'profile_page']);

        // Create roles
        Role::create(['name' => 'superadmin']);
        Role::create(['name' => 'warehouse_admin']);
        Role::create(['name' => 'audit_admin']);
        Role::create(['name' => 'motorpool_admin']);
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'user']);

        $superadmin = User::factory()->create([
            'username' => 'superadmin',
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'superadmin',
        ]);
        $superadmin->assignSingleRole('superadmin');
        $permissions = Permission::pluck('name')->toArray();
        $superadmin->syncPermissions($permissions);

        $warehouse_admin = User::factory()->create([
            'username' => 'warehouseadmin',
            'name' => 'Warehouse Admin',
            'email' => 'warehouseadmin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'warehouse_admin',
        ]);
        $warehouse_admin->assignSingleRole('warehouse_admin');

        $audit_admin = User::factory()->create([
            'username' => 'auditadmin',
            'name' => 'Audit Admin',
            'email' => 'auditadmin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'audit_admin',
        ]);
        $audit_admin->assignSingleRole('audit_admin');

        $motorpool_admin = User::factory()->create([
            'username' => 'motorpooladmin',
            'name' => 'Motorpool Admin',
            'email' => 'motorpooladmin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'motorpool_admin',
        ]);
        $motorpool_admin->assignSingleRole('motorpool_admin');

        $admin = User::factory()->create([
            'username' => 'admin',
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make("1234"),
            'role' => 'admin',
        ]);
        $admin->assignSingleRole('admin');

        $user = User::factory()->create([
            'username' => 'user',
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => Hash::make("1234"),
            'role' => 'superadmin',
        ]);
        $user->assignSingleRole('superadmin');
        $permissions = Permission::pluck('name')->toArray();
        $superadmin->syncPermissions($permissions);

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
            'name' => "DISCONNECTION",
        ]);

        Purpose::factory()->create([
            'name' => "METER READING",
        ]);

        Purpose::factory()->create([
            'name' => "COOP MAINTENANCE",
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

        // Assign permissions
        // $superadmin->givePermissionTo([
        //     'requests_page',
        //     'employees_page',
        //     'reports_page',
        //     'activity_logs_page',
        //     'accounts_page',
        //     'source_settings_page',
        //     'purpose_settings_page',
        //     'vehicle_settings_page',
        //     'allowance_settings_page',
        //     'profile_page',
        // ]);

        // Assign roles to users
        // $user1->assignRole('admin');

        $this->call([
            BarangaySeeder::class,
            FuelDivisorSeeder::class,
        ]);
    }
}
