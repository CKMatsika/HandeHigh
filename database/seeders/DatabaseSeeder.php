<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\School;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            RoleSeeder::class,
            SchoolSeeder::class,
        ]);

        $school = School::first();

        $admin = User::updateOrCreate(
            ['email' => 'admin@mainschool.test'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'school_id' => $school?->id,
            ]
        );

        if (! $admin->hasRole('super-admin')) {
            $admin->assignRole('super-admin');
        }
    }
}
