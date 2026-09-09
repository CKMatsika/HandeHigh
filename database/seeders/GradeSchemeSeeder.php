<?php

namespace Database\Seeders;

use App\Models\GradeBand;
use App\Models\GradeScheme;
use Illuminate\Database\Seeder;

class GradeSchemeSeeder extends Seeder
{
    public function run(): void
    {
        // 1. ZIMSEC O-Level Standard Scheme (Default)
        $oLevelScheme = GradeScheme::firstOrCreate(
            ['name' => 'ZIMSEC O-Level Standard', 'school_id' => null],
            [
                'level' => 'O-Level',
                'description' => 'Official Zimbabwe School Examinations Council (ZIMSEC) aligned Ordinary Level grading band.',
                'effective_from' => '2020',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        $oLevelBands = [
            ['grade' => 'A', 'min_percentage' => 75.00, 'max_percentage' => 100.00, 'description' => 'Distinction', 'is_pass' => true, 'display_order' => 1],
            ['grade' => 'B', 'min_percentage' => 65.00, 'max_percentage' => 74.99, 'description' => 'Merit', 'is_pass' => true, 'display_order' => 2],
            ['grade' => 'C', 'min_percentage' => 50.00, 'max_percentage' => 64.99, 'description' => 'Credit / Pass', 'is_pass' => true, 'display_order' => 3],
            ['grade' => 'D', 'min_percentage' => 45.00, 'max_percentage' => 49.99, 'description' => 'Pass', 'is_pass' => true, 'display_order' => 4],
            ['grade' => 'E', 'min_percentage' => 40.00, 'max_percentage' => 44.99, 'description' => 'Pass', 'is_pass' => true, 'display_order' => 5],
            ['grade' => 'U', 'min_percentage' => 0.00, 'max_percentage' => 39.99, 'description' => 'Ungraded / Fail', 'is_pass' => false, 'display_order' => 6],
        ];

        foreach ($oLevelBands as $band) {
            GradeBand::updateOrCreate(
                ['grade_scheme_id' => $oLevelScheme->id, 'grade' => $band['grade']],
                $band
            );
        }

        // 2. ZIMSEC A-Level Standard Scheme
        $aLevelScheme = GradeScheme::firstOrCreate(
            ['name' => 'ZIMSEC A-Level Standard', 'school_id' => null],
            [
                'level' => 'A-Level',
                'description' => 'ZIMSEC Advanced Level grading scale (A through F).',
                'effective_from' => '2020',
                'is_active' => true,
                'is_default' => false,
            ]
        );

        $aLevelBands = [
            ['grade' => 'A', 'min_percentage' => 75.00, 'max_percentage' => 100.00, 'description' => 'Distinction', 'is_pass' => true, 'display_order' => 1],
            ['grade' => 'B', 'min_percentage' => 65.00, 'max_percentage' => 74.99, 'description' => 'Merit', 'is_pass' => true, 'display_order' => 2],
            ['grade' => 'C', 'min_percentage' => 55.00, 'max_percentage' => 64.99, 'description' => 'Credit', 'is_pass' => true, 'display_order' => 3],
            ['grade' => 'D', 'min_percentage' => 45.00, 'max_percentage' => 54.99, 'description' => 'Pass', 'is_pass' => true, 'display_order' => 4],
            ['grade' => 'E', 'min_percentage' => 40.00, 'max_percentage' => 44.99, 'description' => 'Subsidiary Pass', 'is_pass' => true, 'display_order' => 5],
            ['grade' => 'O', 'min_percentage' => 35.00, 'max_percentage' => 39.99, 'description' => 'Ordinary Level Equivalent', 'is_pass' => true, 'display_order' => 6],
            ['grade' => 'F', 'min_percentage' => 0.00, 'max_percentage' => 34.99, 'description' => 'Fail', 'is_pass' => false, 'display_order' => 7],
        ];

        foreach ($aLevelBands as $band) {
            GradeBand::updateOrCreate(
                ['grade_scheme_id' => $aLevelScheme->id, 'grade' => $band['grade']],
                $band
            );
        }
    }
}
