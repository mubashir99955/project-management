<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run()
    {
        // List of permissions
        $permissions = [
            'create user',
            'view user',
            'view single user',
            'update user',
            'delete user',
            'create tasks',
            'view tasks',
            'view single tasks',
            'update tasks',
            'delete tasks',
            'create role',
            'view role',
            'view single role',
            'update role',
            'delete role',
            'create project',
            'view project',
            'view single project',
            'update project',
            'delete project',
            'create permission',
            'view permission',
            'view single permission',
            'update permission',
            'delete permission',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $user = Role::firstOrCreate(['name' => 'User']);
        $projectOwner = Role::firstOrCreate(['name' => 'Project Owner']);

        // Assign all permissions to Admin
        $admin->givePermissionTo(Permission::all());

        // Assign specific permissions to User
        $user->givePermissionTo([
            'view tasks',
            'view single tasks',
            'update tasks',
        ]);

        // Assign specific permissions to Project Owner
        $projectOwner->givePermissionTo([
            'create tasks',
            'view tasks',
            'view single tasks',
            'update tasks',
            'delete tasks',
            'create project',
            'view project',
            'view single project',
            'update project',
            'delete project',
            'view user',
            'view single user',
        ]);

        // Create default Admin user
        $adminUser = User::firstOrCreate([
            'email' => 'mubashir99955@gmail.com',
        ], [
            'first_name' => 'Mubashar',
            'last_name' => 'Javed',
            'password' => Hash::make('securepassword'),
            'country' => 'Pakistan',
            'phone_number' => '03114595277',
            'account_status' => 'active',
        ]);

        // Assign Admin role to the default user
        $adminUser->assignRole($admin);
    }
}

