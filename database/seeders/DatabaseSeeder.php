<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roleSuperAdmin = Role::create(['name' => 'super-admin']);
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleDriver = Role::create(['name' => 'driver']);
        $roleManager = Role::create(['name' => 'manager']);


        $permissions = [
            'dashboard',
            'manage-users',
            'settings',
            'manage-permissions',
            'manage-roles',
            'manage-logs',

            'transactions',
            'manage-sales',
            'manage-order',
            'approve-sales',
            'manage-po',

            'manage-categories',
            'manage-units',
            'manage-products',

            'manage-customers',

            'payment',
            'manage-distribution',
            'approve-distribution',

            'reports',
            'sales-report',
            'po-report',
            'distribution-report',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        $roleSuperAdmin->givePermissionTo($permissions);

        $superAdmin = User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@mail.com',
        ]);

        $superAdmin->assignRole($roleSuperAdmin);

        $permission_customer = [
            'dashboard',
            'transactions',
            'manage-order',
            'payment',
        ];

        $permission_admin = [
            'dashboard',

            'manage-customers',
            'transactions',
            'manage-sales',
            'manage-po',

            'manage-categories',
            'manage-units',
            'manage-products',
            'approve-sales',

            'payment',
            'manage-distribution',
            'approve-distribution',
        ];

        $permission_driver = [
            'dashboard',
            'transactions',
            'manage-distribution',
        ];

        $permission_manager = [
            'dashboard',

            'manage-customers',
            'transactions',
            'manage-sales',
            'approve-sales',
            'manage-po',

            'manage-categories',
            'manage-units',
            'manage-products',

            'payment',
            'manage-distribution',
            'approve-distribution',

            'reports',
            'sales-report',
            'po-report',
            'distribution-report',
        ];

        $roleCustomer = Role::create(['name' => 'customer']);
        $roleCustomer->givePermissionTo($permission_customer);

        $roleAdmin->givePermissionTo($permission_admin);

        $roleDriver->givePermissionTo($permission_driver);

        $roleManager->givePermissionTo($permission_manager);

        User::factory()->create([
            'name' => 'Customer1',
            'email' => 'customer@mail.com',
            'customer_id' => 'CUST001',
            'address' => 'Jl. Jend. Sudirman No. 1',
        ])->assignRole($roleCustomer);
        User::factory()->create([
            'name' => 'Customer2',
            'email' => 'customer2@mail.com',
            'customer_id' => 'CUST002',
            'address' => 'Jl. Jend. Sudirman No. 2',
        ])->assignRole($roleCustomer);

        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@mail.com',
        ])->assignRole($roleAdmin);

        User::factory()->create([
            'name' => 'Driver1',
            'email' => 'driver@mail.com',
        ])->assignRole($roleDriver);

        User::factory()->create([
            'name' => 'Driver2',
            'email' => 'driver2@mail.com',
        ])->assignRole($roleDriver);

        User::factory()->create([
            'name' => 'Manager',
            'email' => 'manager@mail.com',
        ])->assignRole($roleManager);

        $this->call([
            UnitSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
