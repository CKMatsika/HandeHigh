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
        Schema::table('schemes_of_work', function (Blueprint $table) {
            $table->string('general_topic')->nullable()->after('title');
            $table->text('aims')->nullable()->after('description');
            $table->string('syllabus_reference')->nullable()->after('aims');
            $table->json('cross_cutting_themes')->nullable()->after('syllabus_reference');
        });

        Schema::table('scheme_of_work_items', function (Blueprint $table) {
            $table->date('week_ending')->nullable()->after('week_number');
            $table->text('competencies_skills')->nullable()->after('objectives');
            $table->text('som_media')->nullable()->after('competencies_skills');
            $table->text('facility_equipment')->nullable()->after('som_media');
            $table->text('methods_activities')->nullable()->after('facility_equipment');
            $table->text('evaluation')->nullable()->after('remarks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scheme_of_work_items', function (Blueprint $table) {
            $table->dropColumn([
                'week_ending',
                'competencies_skills',
                'som_media',
                'facility_equipment',
                'methods_activities',
                'evaluation',
            ]);
        });

        Schema::table('schemes_of_work', function (Blueprint $table) {
            $table->dropColumn([
                'general_topic',
                'aims',
                'syllabus_reference',
                'cross_cutting_themes',
            ]);
        });
    }
};
