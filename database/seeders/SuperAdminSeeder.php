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
        $school = \App\Models\School::firstOrCreate(
            ['code' => 'HANDE'],
            [
                'name' => 'Hande High School',
                'email' => 'admin@handehigh.test',
                'phone' => null,
                'address' => null,
                'timezone' => 'Africa/Harare',
                'currency' => 'USD',
                'setup_completed' => true,
            ]
        );

        $superAdmin = \App\Models\User::firstOrCreate(
            ['email' => 'superadmin@handehigh.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'), // Or whatever password you'd like
                'email_verified_at' => now(),
                'school_id' => $school->id,
            ]
        );

        // If the super admin already exists, update their school_id
        if (!$superAdmin->school_id) {
            $superAdmin->update(['school_id' => $school->id]);
        }

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);

        $superAdmin->assignRole($role);
    }
}
