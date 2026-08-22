<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('website')->nullable();
            $table->string('motto')->nullable();
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();
            $table->string('school_type')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'state',
                'country',
                'postal_code',
                'website',
                'motto',
                'description',
                'logo',
                'established_year',
                'school_type',
            ]);
        });
    }
};
