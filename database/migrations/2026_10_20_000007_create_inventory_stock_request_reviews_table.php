<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inventory_stock_request_reviews')) {
            Schema::create('inventory_stock_request_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_stock_request_id')->constrained()->cascadeOnDelete();
                $table->foreignId('reviewed_by')->constrained('users')->restrictOnDelete();
                $table->string('decision', 30);
                $table->text('comments')->nullable();
                $table->timestamp('reviewed_at');
                $table->timestamps();

                $table->index(['inventory_stock_request_id', 'reviewed_at'], 'stock_request_review_date_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stock_request_reviews');
    }
};
