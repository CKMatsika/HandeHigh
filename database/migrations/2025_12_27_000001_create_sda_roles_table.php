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
        Schema::createIfNotExists('sda_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Chairman, Secretary, Committee Member, etc.
            $table->string('slug')->unique(); // chairman, secretary, committee-member
            $table->text('description')->nullable();
            $table->boolean('is_executive')->default(false); // Executive positions like Chairman, Secretary
            $table->integer('sort_order')->default(0); // For ordering in displays
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default SDA roles
        DB::table('sda_roles')->insert([
            [
                'name' => 'Chairman',
                'slug' => 'chairman',
                'description' => 'Head of the School Development Association',
                'is_executive' => true,
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Vice Chairman',
                'slug' => 'vice-chairman',
                'description' => 'Deputy head of the School Development Association',
                'is_executive' => true,
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Secretary',
                'slug' => 'secretary',
                'description' => 'Responsible for documentation and communication',
                'is_executive' => true,
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Treasurer',
                'slug' => 'treasurer',
                'description' => 'Manages financial matters and budget oversight',
                'is_executive' => true,
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Committee Member',
                'slug' => 'committee-member',
                'description' => 'Regular member of various committees',
                'is_executive' => false,
                'sort_order' => 5,
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
        Schema::dropIfExists('sda_roles');
    }
};
