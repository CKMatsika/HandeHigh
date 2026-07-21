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
        Schema::create('sda_committees', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Budget Committee, Finance Committee, Procurement Committee, etc.
            $table->string('slug')->unique(); // budget-committee, finance-committee, procurement-committee
            $table->text('description')->nullable();
            $table->text('mandate')->nullable(); // Committee mandate and responsibilities
            $table->string('chairman_title')->nullable(); // Title for the committee chairman
            $table->boolean('requires_financial_approval')->default(false); // Can approve financial transactions
            $table->boolean('requires_procurement_approval')->default(false); // Can approve procurement requests
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default SDA committees
        DB::table('sda_committees')->insert([
            [
                'name' => 'Executive Committee',
                'slug' => 'executive-committee',
                'description' => 'Main governing body of the SDA',
                'mandate' => 'Overall strategic direction and major decision making for the School Development Association',
                'chairman_title' => 'SDA Chairman',
                'requires_financial_approval' => true,
                'requires_procurement_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Budget Committee',
                'slug' => 'budget-committee',
                'description' => 'Oversees budget planning and financial allocation',
                'mandate' => 'Review and approve annual budgets, monitor spending, ensure financial sustainability',
                'chairman_title' => 'Budget Chairman',
                'requires_financial_approval' => true,
                'requires_procurement_approval' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Finance Committee',
                'slug' => 'finance-committee',
                'description' => 'Manages financial operations and audit',
                'mandate' => 'Financial oversight, audit coordination, financial policy development',
                'chairman_title' => 'Finance Chairman',
                'requires_financial_approval' => true,
                'requires_procurement_approval' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Procurement Committee',
                'slug' => 'procurement-committee',
                'description' => 'Oversees procurement processes and vendor management',
                'mandate' => 'Procurement policy oversight, major contract approval, vendor relationship management',
                'chairman_title' => 'Procurement Chairman',
                'requires_financial_approval' => false,
                'requires_procurement_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Development Committee',
                'slug' => 'development-committee',
                'description' => 'Focuses on school infrastructure and development projects',
                'mandate' => 'Infrastructure planning, development project oversight, fundraising coordination',
                'chairman_title' => 'Development Chairman',
                'requires_financial_approval' => true,
                'requires_procurement_approval' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Academic Committee',
                'slug' => 'academic-committee',
                'description' => 'Advises on academic matters and educational quality',
                'mandate' => 'Academic policy review, curriculum support, educational quality improvement',
                'chairman_title' => 'Academic Chairman',
                'requires_financial_approval' => false,
                'requires_procurement_approval' => false,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sda_committees');
    }
};
