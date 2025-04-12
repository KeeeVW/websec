<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create permissions
        $permissions = [
            'show_users' => 'View users',
            'edit_users' => 'Edit user information',
            'delete_users' => 'Delete users',
            'admin_users' => 'Administer users (roles & permissions)',
        ];

        foreach ($permissions as $name => $display_name) {
            // Check if permission already exists
            $permission = Permission::where('name', $name)->first();
            if (!$permission) {
                Permission::create(['name' => $name, 'display_name' => $display_name]);
            }
        }

        // Create roles if they don't exist
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $userRole = Role::firstOrCreate(['name' => 'user']);

        // Assign permissions to roles
        $adminRole->syncPermissions(array_keys($permissions));
        $employeeRole->syncPermissions(['show_users', 'edit_users']);
        $userRole->syncPermissions(['show_users']);

        // Create admin user
        $admin = User::firstOrNew(['email' => 'admin@example.com']);
        if (!$admin->exists) {
            $admin->fill([
                'name' => 'Admin User',
                'password' => bcrypt('Admin@123'),
                'is_admin' => true,
                'security_question' => 'What is your favorite color?',
                'security_answer' => 'blue',
            ]);
            $admin->save();
            // Assign admin role
            $admin->assignRole('admin');
        }

        // Create regular user
        $user = User::firstOrNew(['email' => 'user@example.com']);
        if (!$user->exists) {
            $user->fill([
                'name' => 'Regular User',
                'password' => bcrypt('User@123'),
                'is_admin' => false,
                'security_question' => 'What is your favorite food?',
                'security_answer' => 'pizza',
            ]);
            $user->save();
            // Assign user role
            $user->assignRole('user');
        }

        // Create employee user
        $employee = User::firstOrNew(['email' => 'employee@example.com']);
        if (!$employee->exists) {
            $employee->fill([
                'name' => 'Employee User',
                'password' => bcrypt('Employee@123'),
                'is_admin' => false,
                'security_question' => 'What is your favorite movie?',
                'security_answer' => 'matrix',
            ]);
            $employee->save();
            // Assign employee role
            $employee->assignRole('employee');
        }
    }
}
