<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $bundledLogoPath = public_path('images/hande-high-logo.png');
        if (!file_exists($bundledLogoPath)) {
            return;
        }

        $bundledBytes = file_get_contents($bundledLogoPath);
        $bundledBase64 = 'data:image/png;base64,' . base64_encode($bundledBytes);

        $schools = School::all();

        foreach ($schools as $school) {
            $isHandeHigh = ($school->id == 2)
                || ($school->code === 'HHS')
                || str_contains(strtolower($school->name ?? ''), 'hande')
                || str_contains(strtolower($school->name ?? ''), 'main')
                || $schools->count() === 1;

            if ($isHandeHigh) {
                $targetPath = "schools/{$school->id}/branding/hande-high-logo.png";
                try {
                    Storage::disk('public')->put($targetPath, $bundledBytes);
                } catch (\Throwable $e) {}

                $school->logo = $targetPath;
                $school->logo_base64 = $bundledBase64;

                if (empty($school->motto) || $school->motto === 'Strive for Excellence') {
                    $school->motto = 'Strive to achieve';
                }

                if (empty($school->display_name) || $school->display_name === 'Main School') {
                    $school->display_name = 'Hande High School';
                }

                if (empty($school->primary_color) || $school->primary_color === '#1e3a8a') {
                    $school->primary_color = '#243282';
                }

                $school->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive
    }
};
