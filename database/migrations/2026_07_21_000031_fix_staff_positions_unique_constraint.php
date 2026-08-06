<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_positions', function (Blueprint $table) {
            $table->dropUnique('staff_positions_slug_unique');
            $table->unique(['school_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('staff_positions', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'slug']);
            $table->unique('slug');
        });
    }
};
