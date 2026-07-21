<?php

namespace Database\Seeders;

use App\Models\StudentPosition;
use App\Models\StudentClub;
use App\Models\StudentSport;
use App\Models\User;
use App\Models\Student;
use Illuminate\Database\Seeder;

class ExtracurricularSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some sample students (users with student role)
        $students = User::role('student')->take(5)->get();

        foreach ($students as $user) {
            // Get the actual student record
            $student = Student::where('user_id', $user->id)->first();
            
            if (!$student) {
                continue; // Skip if no student record found
            }

            // Add student positions
            if ($student->id % 2 == 0) {
                StudentPosition::create([
                    'student_id' => $student->id,
                    'position_type' => 'prefect',
                    'position_title' => 'Senior Prefect',
                    'description' => 'Responsible for maintaining discipline and assisting teachers',
                    'start_date' => now()->subMonths(6),
                    'end_date' => now()->addMonths(6),
                    'is_active' => true,
                ]);
            }

            if ($student->id % 3 == 0) {
                StudentPosition::create([
                    'student_id' => $student->id,
                    'position_type' => 'head_boy',
                    'position_title' => 'Head Boy',
                    'description' => 'Student body leader and representative',
                    'start_date' => now()->subMonths(3),
                    'is_active' => true,
                ]);
            }

            // Add student clubs
            $clubs = [
                ['name' => 'Debate Club', 'type' => 'academic', 'role' => 'President'],
                ['name' => 'Science Club', 'type' => 'academic', 'role' => 'Member'],
                ['name' => 'Drama Club', 'type' => 'arts', 'role' => 'Secretary'],
                ['name' => 'Music Club', 'type' => 'arts', 'role' => 'Member'],
                ['name' => 'Basketball Team', 'type' => 'sports', 'role' => 'Captain'],
                ['name' => 'Football Team', 'type' => 'sports', 'role' => 'Member'],
                ['name' => 'Community Service', 'type' => 'community', 'role' => 'Coordinator'],
            ];

            $selectedClubs = array_rand($clubs, rand(1, 3));
            if (!is_array($selectedClubs)) {
                $selectedClubs = [$selectedClubs];
            }

            foreach ($selectedClubs as $clubIndex) {
                $club = $clubs[$clubIndex];
                StudentClub::create([
                    'student_id' => $student->id,
                    'club_name' => $club['name'],
                    'club_type' => $club['type'],
                    'role' => $club['role'],
                    'description' => "Active participant in {$club['name']} activities",
                    'joined_date' => now()->subMonths(rand(1, 12)),
                    'is_active' => true,
                ]);
            }

            // Add student sports
            $sports = [
                ['name' => 'Basketball', 'category' => 'team_sport', 'position' => 'Point Guard'],
                ['name' => 'Football', 'category' => 'team_sport', 'position' => 'Forward'],
                ['name' => 'Tennis', 'category' => 'individual_sport', 'position' => 'Player'],
                ['name' => 'Swimming', 'category' => 'individual_sport', 'position' => 'Swimmer'],
                ['name' => 'Athletics', 'category' => 'athletics', 'position' => 'Sprinter'],
                ['name' => 'Chess', 'category' => 'individual_sport', 'position' => 'Player'],
            ];

            $selectedSports = array_rand($sports, rand(1, 2));
            if (!is_array($selectedSports)) {
                $selectedSports = [$selectedSports];
            }

            foreach ($selectedSports as $sportIndex) {
                $sport = $sports[$sportIndex];
                StudentSport::create([
                    'student_id' => $student->id,
                    'sport_name' => $sport['name'],
                    'sport_category' => $sport['category'],
                    'position' => $sport['position'],
                    'team_level' => 'Varsity',
                    'achievements' => 'Participated in inter-school competitions',
                    'started_date' => now()->subMonths(rand(6, 18)),
                    'is_active' => true,
                ]);
            }
        }

        echo "Extracurricular activities seeded successfully!\n";
    }
}
