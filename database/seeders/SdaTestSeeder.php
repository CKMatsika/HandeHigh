<?php

namespace Database\Seeders;

use App\Models\SdaCommittee;
use App\Models\SdaCommitteeMember;
use App\Models\SdaRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SdaTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get or create SDA roles
        $chairmanRole = SdaRole::firstOrCreate(['name' => 'Chairman'], [
            'description' => 'Committee Chairman',
            'slug' => 'chairman',
            'is_executive' => true,
        ]);

        $secretaryRole = SdaRole::firstOrCreate(['name' => 'Secretary'], [
            'description' => 'Committee Secretary',
            'slug' => 'secretary',
            'is_executive' => true,
        ]);

        $treasurerRole = SdaRole::firstOrCreate(['name' => 'Treasurer'], [
            'description' => 'Committee Treasurer',
            'slug' => 'treasurer',
            'is_executive' => true,
        ]);

        $financeRole = SdaRole::firstOrCreate(['name' => 'Finance Committee'], [
            'description' => 'Finance Committee Member',
            'slug' => 'finance-committee',
            'is_executive' => false,
        ]);

        $procurementRole = SdaRole::firstOrCreate(['name' => 'Procurement Committee'], [
            'description' => 'Procurement Committee Member',
            'slug' => 'procurement-committee',
            'is_executive' => false,
        ]);

        $memberRole = SdaRole::firstOrCreate(['name' => 'Member'], [
            'description' => 'Regular Committee Member',
            'slug' => 'member',
            'is_executive' => false,
        ]);

        // Create a test SDA committee
        $committee = SdaCommittee::firstOrCreate([
            'name' => 'Main SDA Committee',
            'description' => 'Main School Development Association Committee',
        ], [
            'slug' => 'main-sda-committee',
            'is_active' => true,
            'requires_financial_approval' => true,
            'requires_procurement_approval' => true,
        ]);

        // Get the first user (likely the admin/super-admin)
        $user = User::first();
        
        if ($user) {
            // Assign the user as Chairman of the SDA committee
            SdaCommitteeMember::firstOrCreate([
                'user_id' => $user->id,
                'sda_committee_id' => $committee->id,
            ], [
                'sda_role_id' => $chairmanRole->id,
                'is_active' => true,
                'appointment_date' => now(),
            ]);

            echo "Assigned user {$user->name} as Chairman of SDA Committee\n";
        }

        // Create some additional test users with different roles
        $testUsers = [
            ['email' => 'secretary@test.com', 'name' => 'Test Secretary', 'role' => $secretaryRole],
            ['email' => 'treasurer@test.com', 'name' => 'Test Treasurer', 'role' => $treasurerRole],
            ['email' => 'finance@test.com', 'name' => 'Test Finance Member', 'role' => $financeRole],
            ['email' => 'procurement@test.com', 'name' => 'Test Procurement Member', 'role' => $procurementRole],
            ['email' => 'member@test.com', 'name' => 'Test Member', 'role' => $memberRole],
        ];

        foreach ($testUsers as $testUser) {
            $user = User::firstOrCreate(['email' => $testUser['email']], [
                'name' => $testUser['name'],
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);

            SdaCommitteeMember::firstOrCreate([
                'user_id' => $user->id,
                'sda_committee_id' => $committee->id,
            ], [
                'sda_role_id' => $testUser['role']->id,
                'is_active' => true,
                'appointment_date' => now(),
            ]);

            echo "Created user {$testUser['name']} with role {$testUser['role']->name}\n";
        }

        echo "SDA Test Seeder completed successfully!\n";
    }
}
