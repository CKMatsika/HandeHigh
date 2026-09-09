<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = \App\Models\User::firstOrCreate(
            ['email' => 'superadmin@handehigh.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'), // Or whatever password you'd like
                'email_verified_at' => now(),
            ]
        );

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $superAdmin->assignRole($role);
    }
}
