<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ict_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->string('category')->default('erp_support');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'status']);
            $table->index(['school_id', 'user_id']);
        });

        Schema::create('ict_lab_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('device_name');
            $table->string('device_code', 50)->nullable();
            $table->string('location')->default('Computer Lab');
            $table->enum('condition_status', ['working', 'needs_attention', 'faulty', 'under_repair'])->default('working');
            $table->date('last_checked_at')->nullable();
            $table->foreignId('last_checked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'condition_status']);
        });

        // Seed initial starter devices for the school lab
        try {
            $schools = School::all();
            foreach ($schools as $school) {
                if (\Illuminate\Support\Facades\DB::table('ict_lab_items')->where('school_id', $school->id)->count() === 0) {
                    $defaultDevices = [
                        ['device_name' => 'Lab Workstations (Batch A)', 'device_code' => 'ICT-PC-01-20', 'location' => 'Computer Lab 1', 'condition_status' => 'working', 'notes' => '20 Desktop computers with Windows and browser access'],
                        ['device_name' => 'Lab Workstations (Batch B)', 'device_code' => 'ICT-PC-21-40', 'location' => 'Computer Lab 1', 'condition_status' => 'working', 'notes' => '20 Desktop computers connected to local LAN'],
                        ['device_name' => 'HP LaserJet Printer', 'device_code' => 'ICT-PRN-01', 'location' => 'Administration Office', 'condition_status' => 'working', 'notes' => 'Central network printer for reports and registers'],
                        ['device_name' => 'Epson Digital Projector', 'device_code' => 'ICT-PRJ-01', 'location' => 'Computer Lab 1', 'condition_status' => 'working', 'notes' => 'Used for practical demos & teacher workshops'],
                        ['device_name' => 'Main Wi-Fi & Internet Gateway', 'device_code' => 'ICT-NET-01', 'location' => 'Admin Server Room', 'condition_status' => 'working', 'notes' => 'Primary school broadband link with backup UPS'],
                        ['device_name' => 'Staffroom Mobile Hotspot / AP', 'device_code' => 'ICT-NET-02', 'location' => 'Staffroom', 'condition_status' => 'working', 'notes' => 'Access point for teachers preparing schemes and marks'],
                    ];

                    foreach ($defaultDevices as $dev) {
                        \Illuminate\Support\Facades\DB::table('ict_lab_items')->insert([
                            'school_id' => $school->id,
                            'device_name' => $dev['device_name'],
                            'device_code' => $dev['device_code'],
                            'location' => $dev['location'],
                            'condition_status' => $dev['condition_status'],
                            'last_checked_at' => now(),
                            'notes' => $dev['notes'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Safe fallback during tests
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ict_lab_items');
        Schema::dropIfExists('ict_tickets');
    }
};
