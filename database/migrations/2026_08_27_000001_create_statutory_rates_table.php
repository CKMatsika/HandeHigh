<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statutory_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('rate_type')->comment('paye_bracket, aids_levy, nssa_employee, nssa_employer, nssa_max_earnings, nec_rule, trade_union_rule, medical_aid_credit');
            $table->string('currency', 10)->default('USD')->comment('USD, ZWG, ALL');
            $table->decimal('bracket_min', 14, 2)->nullable();
            $table->decimal('bracket_max', 14, 2)->nullable();
            $table->decimal('rate_percentage', 8, 4)->nullable();
            $table->decimal('flat_amount', 14, 2)->nullable();
            $table->string('sector_code')->nullable()->comment('For NEC rules e.g. NEC-EDU, NEC-COMM');
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index(['school_id', 'rate_type', 'currency', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statutory_rates');
    }
};
