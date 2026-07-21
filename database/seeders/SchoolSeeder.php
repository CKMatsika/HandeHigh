<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\School;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        School::firstOrCreate(
            ['code' => 'MAIN'],
            [
                'name' => 'Main School',
                'email' => 'info@mainschool.test',
                'phone' => null,
                'address' => null,
                'timezone' => 'Africa/Harare',
                'currency' => 'USD',
            ]
        );
    }
}
