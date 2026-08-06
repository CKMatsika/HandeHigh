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
        Schema::createIfNotExists('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->onDelete('cascade');
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('category', [
                // Assets
                'current_asset', 'fixed_asset', 'bank', 'cash', 'receivable',
                // Liabilities
                'current_liability', 'long_term_liability', 'payable',
                // Equity
                'equity', 'retained_earnings',
                // Revenue
                'tuition_revenue', 'other_revenue', 'grants',
                // Expense
                'salary_expense', 'utility_expense', 'maintenance_expense', 'supply_expense', 'other_expense'
            ]);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->onDelete('set null');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['school_id', 'type']);
            $table->index(['school_id', 'code']);
            $table->index(['school_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
