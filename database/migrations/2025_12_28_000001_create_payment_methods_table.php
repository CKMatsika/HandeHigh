<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::createIfNotExists('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // eco, one, omari, innbucks, visa, master, etc.
            $table->string('type'); // mobile_money, card, bank_transfer, online
            $table->string('provider'); // ecocash, onemoney, omari, innbucks, visa, mastercard, etc.
            $table->string('currency', 3)->default('USD'); // USD, ZWL, etc.
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->json('config')->nullable(); // Store additional configuration like merchant codes, etc.
            $table->decimal('transaction_fee_percentage', 5, 2)->default(0);
            $table->decimal('fixed_transaction_fee', 10, 2)->default(0);
            $table->decimal('minimum_amount', 10, 2)->default(0);
            $table->decimal('maximum_amount', 10, 2)->nullable();
            $table->timestamps();
            
            $table->index(['is_active', 'type']);
            $table->index(['currency', 'is_active']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};
