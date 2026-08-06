<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::createIfNotExists('sda_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sda_committee_id')->constrained()->onDelete('cascade');
            $table->foreignId('submitted_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->enum('report_type', ['chairman', 'secretary', 'treasurer', 'committee', 'special', 'annual'])->default('committee');
            $table->text('executive_summary')->nullable();
            $table->longText('content');
            $table->text('recommendations')->nullable();
            $table->text('conclusions')->nullable();
            $table->date('report_date');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->enum('status', ['draft', 'submitted', 'review', 'approved', 'published', 'archived'])->default('draft');
            $table->text('review_notes')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('file_path')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sda_reports');
    }
};
