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
    }
}
