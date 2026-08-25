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
        Schema::table('schools', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('telephone')->nullable()->after('phone');
            $table->string('mobile')->nullable()->after('telephone');
            $table->string('whatsapp')->nullable()->after('mobile');
            $table->string('postal_address')->nullable()->after('address');
            $table->string('registration_number')->nullable()->after('school_type');
            $table->string('zimsec_center_number')->nullable()->after('registration_number');
            $table->string('principal_name')->nullable()->after('zimsec_center_number');
            $table->string('bursar_name')->nullable()->after('principal_name');
            $table->string('administrator_name')->nullable()->after('bursar_name');
            $table->string('primary_color', 20)->default('#1e3a8a')->after('administrator_name');
            $table->string('secondary_color', 20)->default('#d97706')->after('primary_color');
            $table->text('footer_text')->nullable()->after('secondary_color');
            $table->string('bank_name')->nullable()->after('footer_text');
            $table->string('bank_account_name')->nullable()->after('bank_name');
            $table->string('bank_account_number')->nullable()->after('bank_account_name');
            $table->string('bank_branch')->nullable()->after('bank_account_number');
            $table->text('payment_instructions')->nullable()->after('bank_branch');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->json('school_snapshot')->nullable()->after('status');
        });

        Schema::table('receipts', function (Blueprint $table) {
            $table->json('school_snapshot')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('school_snapshot');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('school_snapshot');
        });

        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'telephone',
                'mobile',
                'whatsapp',
                'postal_address',
                'registration_number',
                'zimsec_center_number',
                'principal_name',
                'bursar_name',
                'administrator_name',
                'primary_color',
                'secondary_color',
                'footer_text',
                'bank_name',
                'bank_account_name',
                'bank_account_number',
                'bank_branch',
                'payment_instructions',
            ]);
        });
    }
};
