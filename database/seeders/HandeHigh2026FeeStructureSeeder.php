<?php

namespace Database\Seeders;

use App\Models\School;
use App\Services\Finance\HandeFeeVotesService;
use Illuminate\Database\Seeder;

class HandeHigh2026FeeStructureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = School::all();

        foreach ($schools as $school) {
            HandeFeeVotesService::syncSchoolVotes($school, '2026', 'Term 3');
        }
    }
}
