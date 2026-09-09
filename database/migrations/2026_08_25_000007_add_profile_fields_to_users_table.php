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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email');
            }
            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address', 500)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 20)->nullable()->after('address');
            }
            if (! Schema::hasColumn('users', 'date_of_birth')) {
                $table->date('date_of_birth')->nullable()->after('gender');
            }
            if (! Schema::hasColumn('users', 'profile_photo')) {
                $table->string('profile_photo', 255)->nullable()->after('date_of_birth');
            }
            if (! Schema::hasColumn('users', 'emergency_contact')) {
                $table->string('emergency_contact', 255)->nullable()->after('profile_photo');
            }
            if (! Schema::hasColumn('users', 'emergency_phone')) {
                $table->string('emergency_phone', 20)->nullable()->after('emergency_contact');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'address',
                'gender',
                'date_of_birth',
                'profile_photo',
                'emergency_contact',
                'emergency_phone',
            ]);
        });
    }
};
