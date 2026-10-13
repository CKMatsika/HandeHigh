<?php

use App\Models\Project;
use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure project_tasks table exists
        Schema::create('project_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('phase_milestone')->default('Phase 1: Foundation & Onboarding');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'blocked'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('progress_percent')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'project_id']);
            $table->index(['project_id', 'status']);
        });

        // 2. Ensure ict-coordinator role exists
        try {
            Role::firstOrCreate(['name' => 'ict-coordinator', 'guard_name' => 'web']);
        } catch (\Throwable $e) {
            // Ignore if role tables not ready in specific test sequence
        }

        // 3. Seed default ERP Implementation project for existing schools if not already present
        try {
            $schools = School::all();
            foreach ($schools as $school) {
                $erpProject = Project::firstOrCreate(
                    [
                        'school_id' => $school->id,
                        'code' => 'PRJ-ERP-2026',
                    ],
                    [
                        'name' => 'Hande High School ERP Implementation',
                        'description' => 'Official digital transformation rollout project covering teacher portals, class allocations, schemes of work, continuous assessment, and school financial operations.',
                        'start_date' => now()->startOfMonth(),
                        'end_date' => now()->addMonths(4)->endOfMonth(),
                        'budget_amount' => 0,
                        'status' => 'active',
                        'project_type' => 'technology',
                    ]
                );

                // Add starter roadmap tasks if none exist
                if (\Illuminate\Support\Facades\DB::table('project_tasks')->where('project_id', $erpProject->id)->count() === 0) {
                    $starterTasks = [
                        // Phase 1
                        [
                            'title' => 'Teacher Portal Onboarding & Password Setup',
                            'description' => 'Verify all subject teachers have login accounts, email credentials, and active portal profiles.',
                            'phase_milestone' => 'Phase 1: Foundation & Onboarding',
                            'priority' => 'urgent',
                            'status' => 'completed',
                            'progress_percent' => 100,
                            'due_date' => now()->subDays(2),
                        ],
                        [
                            'title' => 'Form 2 Mathematics Class Allocations Verification',
                            'description' => 'Confirm Mr. Sengedza is allocated all 4 Form 2 Mathematics classes with curriculum allocations active.',
                            'phase_milestone' => 'Phase 1: Foundation & Onboarding',
                            'priority' => 'high',
                            'status' => 'completed',
                            'progress_percent' => 100,
                            'due_date' => now()->subDays(1),
                        ],
                        [
                            'title' => 'Computer Lab Readiness & Internet Inspection',
                            'description' => 'Audit computer lab workstations, projector in hall, and staffroom network routers.',
                            'phase_milestone' => 'Phase 1: Foundation & Onboarding',
                            'priority' => 'high',
                            'status' => 'in_progress',
                            'progress_percent' => 60,
                            'due_date' => now()->addDays(3),
                        ],
                        // Phase 2
                        [
                            'title' => 'Teacher Schemes of Work Digital Preparation',
                            'description' => 'Guide teachers in submitting Term 1 schemes of work digitally via Teacher Portal.',
                            'phase_milestone' => 'Phase 2: Academic Operations & Timetable',
                            'priority' => 'high',
                            'status' => 'in_progress',
                            'progress_percent' => 40,
                            'due_date' => now()->addDays(7),
                        ],
                        [
                            'title' => 'Class Teacher Daily Attendance Register Rollout',
                            'description' => 'Train designated administrative class teachers on morning roll call attendance via portal.',
                            'phase_milestone' => 'Phase 2: Academic Operations & Timetable',
                            'priority' => 'medium',
                            'status' => 'pending',
                            'progress_percent' => 0,
                            'due_date' => now()->addDays(12),
                        ],
                        // Phase 3
                        [
                            'title' => 'Subject Mark Entry & Continuous Assessment Testing',
                            'description' => 'Test mid-term and end-of-term score entry by subject teachers in Mathematics and Sciences.',
                            'phase_milestone' => 'Phase 3: Assessment & Student Reports',
                            'priority' => 'high',
                            'status' => 'pending',
                            'progress_percent' => 0,
                            'due_date' => now()->addDays(20),
                        ],
                        [
                            'title' => 'End-of-Term Report Card Template Verification',
                            'description' => 'Confirm Headmaster signature, school crest branding, and remarks format on printable PDF reports.',
                            'phase_milestone' => 'Phase 3: Assessment & Student Reports',
                            'priority' => 'medium',
                            'status' => 'pending',
                            'progress_percent' => 0,
                            'due_date' => now()->addDays(28),
                        ],
                        // Phase 4
                        [
                            'title' => 'Finance & Fee Billing Reconciliation with Bursar',
                            'description' => 'Review term fee structures, student invoice balances, and daily receipting workflow.',
                            'phase_milestone' => 'Phase 4: Finance & Operations',
                            'priority' => 'medium',
                            'status' => 'pending',
                            'progress_percent' => 0,
                            'due_date' => now()->addDays(35),
                        ],
                    ];

                    foreach ($starterTasks as $task) {
                        \Illuminate\Support\Facades\DB::table('project_tasks')->insert([
                            'school_id' => $school->id,
                            'project_id' => $erpProject->id,
                            'title' => $task['title'],
                            'description' => $task['description'],
                            'phase_milestone' => $task['phase_milestone'],
                            'priority' => $task['priority'],
                            'status' => $task['status'],
                            'progress_percent' => $task['progress_percent'],
                            'due_date' => $task['due_date'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback during testing or fresh migrations
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_tasks');
    }
};
