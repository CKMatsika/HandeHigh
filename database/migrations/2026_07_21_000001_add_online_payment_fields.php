<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add payment_reference and paynow_poll_url to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('payment_reference', 30)->nullable()->unique()->after('status')
                  ->comment('Unique reference parents quote at the bank e.g. SCH-0001-0045');
        });

        // Enhance payments table with online payment fields
        Schema::table('payments', function (Blueprint $table) {
            $table->string('source', 30)->default('manual')->after('status')
                  ->comment('manual | paynow | bank_webhook | bank_statement');
            $table->string('paynow_reference', 100)->nullable()->after('source')
                  ->comment('Paynow pollUrl stored for verification');
            $table->string('paynow_poll_url', 500)->nullable()->after('paynow_reference');
            $table->json('webhook_payload')->nullable()->after('paynow_poll_url')
                  ->comment('Raw webhook payload for audit');
            $table->timestamp('receipt_printed_at')->nullable()->after('webhook_payload');
            $table->boolean('receipt_pending_print')->default(false)->after('receipt_printed_at');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_reference');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'source', 'paynow_reference', 'paynow_poll_url',
                'webhook_payload', 'receipt_printed_at', 'receipt_pending_print',
            ]);
        });
    }
};
