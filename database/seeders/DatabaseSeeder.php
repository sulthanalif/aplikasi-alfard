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


        $permissions = [
            'dashboard',
            'manage-users',
            'settings',
            'manage-permissions',
            'manage-roles',
            'manage-logs',

            'transactions',
            'manage-sales',
            'manage-po',

            'manage-categories',
            'manage-units',
            'manage-products',
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
            'manage-sales',
        ];

        $roleCustomer = Role::create(['name' => 'customer']);
        $roleCustomer->givePermissionTo($permission_customer);

        User::factory()->create([
            'name' => 'Customer',
            'email' => 'customer@mail.com',
            'customer_id' => 'CUST001',
            'address' => 'Jl. Jend. Sudirman No. 1',
        ])->assignRole($roleCustomer);

        $this->call([
            UnitSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
