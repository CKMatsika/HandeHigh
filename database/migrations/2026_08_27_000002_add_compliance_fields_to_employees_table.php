<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('national_id')->nullable()->after('employee_id');
            $table->string('zimra_tin')->nullable()->after('national_id');
            $table->string('nssa_number')->nullable()->after('zimra_tin');
            $table->string('nec_sector_code')->nullable()->after('nssa_number')->comment('e.g., NEC-EDU, NEC-COMM');
            $table->boolean('trade_union_member')->default(false)->after('nec_sector_code');
            $table->decimal('trade_union_rate', 8, 4)->default(0)->after('trade_union_member')->comment('Percentage of basic salary if percentage based');
            $table->decimal('trade_union_flat_amount', 12, 2)->default(0)->after('trade_union_rate')->comment('Flat fee if flat based');
            $table->decimal('medical_aid_usd', 12, 2)->default(0)->after('trade_union_flat_amount');
            $table->decimal('medical_aid_zwg', 12, 2)->default(0)->after('medical_aid_usd');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'national_id',
                'zimra_tin',
                'nssa_number',
                'nec_sector_code',
                'trade_union_member',
                'trade_union_rate',
                'trade_union_flat_amount',
                'medical_aid_usd',
                'medical_aid_zwg',
            ]);
        });
    }
};
