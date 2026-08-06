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
        Schema::createIfNotExists('credit_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('credit_note_number')->unique();
            $table->date('credit_note_date');
            $table->enum('type', ['customer', 'student']); // For customers or students
            $table->foreignId('customer_id')->nullable()->constrained('customers')->onDelete('restrict');
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('restrict');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->onDelete('set null');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('applied_amount', 15, 2)->default(0);
            $table->decimal('balance', 15, 2)->default(0);
            $table->enum('status', ['draft', 'issued', 'applied', 'cancelled'])->default('draft');
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'status']);
            $table->index(['customer_id']);
            $table->index(['student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_notes');
    }
};
