<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\StaffPosition;
use Illuminate\Database\Seeder;

class StaffPositionSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::all();

        if ($schools->isEmpty()) {
            $this->command?->warn('No schools found. Create a school first.');
            return;
        }

        $positions = [
            ['name' => 'Class Teacher', 'slug' => 'class-teacher', 'category' => 'academic', 'description' => 'Responsible for a specific class form'],
            ['name' => 'Head of Department', 'slug' => 'head-of-department', 'category' => 'academic', 'description' => 'Leads an academic department'],
            ['name' => 'Deputy Head', 'slug' => 'deputy-head', 'category' => 'administration', 'description' => 'Deputy head of school'],
            ['name' => 'Sports Master', 'slug' => 'sports-master', 'category' => 'co-curricular', 'description' => 'Oversees sports and athletic programs'],
            ['name' => 'Boarding Master', 'slug' => 'boarding-master', 'category' => 'boarding', 'description' => 'Manages a boarding house or dormitory'],
            ['name' => 'Guidance & Counselling', 'slug' => 'guidance-counselling', 'category' => 'support', 'description' => 'Student welfare and counselling services'],
            ['name' => 'Examinations Officer', 'slug' => 'examinations-officer', 'category' => 'academic', 'description' => 'Manages school examinations'],
            ['name' => 'ICT Coordinator', 'slug' => 'ict-coordinator', 'category' => 'support', 'description' => 'Manages IT infrastructure and systems'],
            ['name' => 'Librarian', 'slug' => 'librarian', 'category' => 'support', 'description' => 'Manages the school library'],
            ['name' => 'Senior Teacher', 'slug' => 'senior-teacher', 'category' => 'academic', 'description' => 'Senior teaching position with mentoring duties'],
        ];

        foreach ($schools as $school) {
            foreach ($positions as $pos) {
                StaffPosition::firstOrCreate(
                    ['school_id' => $school->id, 'slug' => $pos['slug']],
                    array_merge($pos, ['school_id' => $school->id])
                );
            }

            $this->command?->info("Staff positions seeded for school: {$school->name}");
        }
    }
}
