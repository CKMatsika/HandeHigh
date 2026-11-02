<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_student_payment_review_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_transaction_id')->unique()->constrained('bank_transactions')->cascadeOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('deferred_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('deferred_at')->nullable();
            $table->foreignId('restored_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('restored_at')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'student_id', 'deferred_at'], 'bank_student_review_queue_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_student_payment_review_states');
    }
};
