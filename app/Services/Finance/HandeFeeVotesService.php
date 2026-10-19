<?php

namespace App\Services\Finance;

use App\Models\FeeStructure;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class HandeFeeVotesService
{
    /**
     * Standard MOPSE / Hande High School Vote Categories
     */
    public static function getStandardVotes(): array
    {
        return [
            'TUITION' => [
                'name' => 'Tuition',
                'category' => 'tuition',
                'priority' => 1,
            ],
            'BUILDING_FUND' => [
                'name' => 'Building Fund',
                'category' => 'levy',
                'priority' => 2,
            ],
            'SPORTS' => [
                'name' => 'Sports, Arts And Culture',
                'category' => 'levy',
                'priority' => 3,
            ],
            'PRACTICALS_ICT' => [
                'name' => 'Practicals / ICT Levy',
                'category' => 'levy',
                'priority' => 4,
            ],
            'GPF_ADMIN' => [
                'name' => 'General Purpose Fund & Administration Cost',
                'category' => 'levy',
                'priority' => 5,
            ],
            'SCIENCE_LEVY' => [
                'name' => 'Science Levy',
                'category' => 'subject',
                'priority' => 6,
            ],
            'BOARDING_FEES' => [
                'name' => 'Boarding Fees',
                'category' => 'boarding',
                'priority' => 7,
            ],
            'FOOD' => [
                'name' => 'Food (Lunch Monday - Friday)',
                'category' => 'other',
                'priority' => 7,
            ],
            'COUNCIL_LEVY' => [
                'name' => 'Council Levy',
                'category' => 'levy',
                'priority' => 8,
            ],
            'BSPZ' => [
                'name' => 'B.S.P.Z.',
                'category' => 'levy',
                'priority' => 9,
            ],
        ];
    }

    /**
     * Official Signatory Committee according to Hande High School schedule
     */
    public static function getAuthorisedSignatories(): array
    {
        return [
            ['name' => 'MACHAKAIRE N.B', 'role' => 'HEAD'],
            ['name' => 'ZARIRO J', 'role' => 'DEPUTY HEAD'],
            ['name' => 'MANYENGA B', 'role' => 'CHAIRPERSON'],
            ['name' => 'KARIMBA A', 'role' => 'VICE-CHAIRPERSON'],
            ['name' => 'GUYO A', 'role' => 'COMMITTEE MEMBER'],
            ['name' => 'MUTENGO P', 'role' => 'SECRETARY'],
            ['name' => 'DUBE A', 'role' => 'TREASURER'],
            ['name' => 'MUNEMERO E', 'role' => 'SNR LADY'],
            ['name' => 'BERAH D', 'role' => 'SNR TEACHER'],
        ];
    }

    /**
     * Seed or sync the official 2026 Hande High School Fee Structure
     */
    public static function syncSchoolVotes(School $school, string $academicYear = '2026', string $term = 'Term 3'): array
    {
        return DB::transaction(function () use ($school, $academicYear, $term) {
            // 1. Boarding Package ($470.00)
            $boardingParent = FeeStructure::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year' => $academicYear,
                    'term' => $term,
                    'code' => 'BOARD-PKG-' . $academicYear,
                ],
                [
                    'label' => 'Boarding Student Fees (Inclusive of 1 science subject)',
                    'category' => 'boarding',
                    'residency_type' => 'boarding',
                    'amount' => 470.00,
                    'allocation_priority' => 1,
                    'is_optional' => false,
                    'parent_id' => null,
                ]
            );

            $boardingVotes = [
                ['code' => 'B-BRD-' . $academicYear, 'vote_code' => 'BOARDING_FEES', 'label' => 'Boarding Fees', 'amount' => 380.00, 'category' => 'boarding', 'priority' => 7],
                ['code' => 'B-TUI-' . $academicYear, 'vote_code' => 'TUITION', 'label' => 'Tuition', 'amount' => 20.00, 'category' => 'tuition', 'priority' => 1],
                ['code' => 'B-BLD-' . $academicYear, 'vote_code' => 'BUILDING_FUND', 'label' => 'Building Fund', 'amount' => 15.00, 'category' => 'levy', 'priority' => 2],
                ['code' => 'B-GPF-' . $academicYear, 'vote_code' => 'GPF_ADMIN', 'label' => 'General Purpose Fund', 'amount' => 10.00, 'category' => 'levy', 'priority' => 5],
                ['code' => 'B-SPT-' . $academicYear, 'vote_code' => 'SPORTS', 'label' => 'Sports', 'amount' => 10.00, 'category' => 'levy', 'priority' => 3],
                ['code' => 'B-ICT-' . $academicYear, 'vote_code' => 'PRACTICALS_ICT', 'label' => 'ICT Levy', 'amount' => 15.00, 'category' => 'levy', 'priority' => 4],
                ['code' => 'B-SCI-' . $academicYear, 'vote_code' => 'SCIENCE_LEVY', 'label' => 'Science Levy Per Subject (Chemistry/Biology/Physics/Crop Science)', 'amount' => 20.00, 'category' => 'subject', 'priority' => 6],
            ];

            foreach ($boardingVotes as $bv) {
                FeeStructure::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year' => $academicYear,
                        'term' => $term,
                        'code' => $bv['code'],
                    ],
                    [
                        'parent_id' => $boardingParent->id,
                        'residency_type' => 'boarding',
                        'vote_code' => $bv['vote_code'],
                        'label' => $bv['label'],
                        'amount' => $bv['amount'],
                        'category' => $bv['category'],
                        'allocation_priority' => $bv['priority'],
                        'is_optional' => false,
                    ]
                );
            }

            // 2. Day-Scholar Package ($135.00)
            $dayParent = FeeStructure::updateOrCreate(
                [
                    'school_id' => $school->id,
                    'academic_year' => $academicYear,
                    'term' => $term,
                    'code' => 'DAY-PKG-' . $academicYear,
                ],
                [
                    'label' => 'Day-Scholar Student Fees (Inclusive of 1 science subject)',
                    'category' => 'tuition',
                    'residency_type' => 'day',
                    'amount' => 135.00,
                    'allocation_priority' => 1,
                    'is_optional' => false,
                    'parent_id' => null,
                ]
            );

            $dayVotes = [
                ['code' => 'D-FOD-' . $academicYear, 'vote_code' => 'FOOD', 'label' => 'Food (Lunch only Monday - Friday)', 'amount' => 45.00, 'category' => 'other', 'priority' => 7],
                ['code' => 'D-TUI-' . $academicYear, 'vote_code' => 'TUITION', 'label' => 'Tuition', 'amount' => 20.00, 'category' => 'tuition', 'priority' => 1],
                ['code' => 'D-BLD-' . $academicYear, 'vote_code' => 'BUILDING_FUND', 'label' => 'Building Fund', 'amount' => 15.00, 'category' => 'levy', 'priority' => 2],
                ['code' => 'D-GPF-' . $academicYear, 'vote_code' => 'GPF_ADMIN', 'label' => 'General Purpose Fund', 'amount' => 10.00, 'category' => 'levy', 'priority' => 5],
                ['code' => 'D-SPT-' . $academicYear, 'vote_code' => 'SPORTS', 'label' => 'Sports', 'amount' => 10.00, 'category' => 'levy', 'priority' => 3],
                ['code' => 'D-ICT-' . $academicYear, 'vote_code' => 'PRACTICALS_ICT', 'label' => 'ICT Levy', 'amount' => 15.00, 'category' => 'levy', 'priority' => 4],
                ['code' => 'D-SCI-' . $academicYear, 'vote_code' => 'SCIENCE_LEVY', 'label' => 'Science Levy Per Subject (Chemistry/Biology/Physics/Crop Science)', 'amount' => 20.00, 'category' => 'subject', 'priority' => 6],
            ];

            foreach ($dayVotes as $dv) {
                FeeStructure::updateOrCreate(
                    [
                        'school_id' => $school->id,
                        'academic_year' => $academicYear,
                        'term' => $term,
                        'code' => $dv['code'],
                    ],
                    [
                        'parent_id' => $dayParent->id,
                        'residency_type' => 'day',
                        'vote_code' => $dv['vote_code'],
                        'label' => $dv['label'],
                        'amount' => $dv['amount'],
                        'category' => $dv['category'],
                        'allocation_priority' => $dv['priority'],
                        'is_optional' => false,
                    ]
                );
            }

            return [
                'boarding_parent' => $boardingParent,
                'day_parent' => $dayParent,
            ];
        });
    }
}
