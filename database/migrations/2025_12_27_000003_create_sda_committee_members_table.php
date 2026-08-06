<?php

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
        Schema::createIfNotExists('sda_committee_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('sda_committee_id')->constrained()->onDelete('cascade');
            $table->foreignId('sda_role_id')->constrained()->onDelete('cascade');
            $table->date('appointment_date'); // When appointed to the committee
            $table->date('end_date')->nullable(); // When term ends
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable(); // Additional notes about the appointment
            $table->timestamps();

            // Ensure a user can only have one role per committee
            $table->unique(['user_id', 'sda_committee_id', 'is_active'], 'unique_active_committee_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sda_committee_members');
    }
};
