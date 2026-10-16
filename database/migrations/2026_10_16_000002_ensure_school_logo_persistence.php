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
        $bundledBase64 = null;
        $bundledBytes = null;

        if (file_exists($bundledLogoPath)) {
            $bundledBytes = file_get_contents($bundledLogoPath);
            $bundledBase64 = 'data:image/png;base64,' . base64_encode($bundledBytes);
        }

        $schools = School::all();

        foreach ($schools as $school) {
            $updated = false;

            // 1. If base64 exists, ensure physical file exists on disk
            if (!empty($school->logo_base64)) {
                try {
                    $targetPath = $school->logo ?: ("schools/{$school->id}/branding/logo.png");
                    if (!Storage::disk('public')->exists($targetPath)) {
                        if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.+)$/', $school->logo_base64, $matches)) {
                            $binary = base64_decode($matches[2]);
                            if ($binary !== false) {
                                Storage::disk('public')->put($targetPath, $binary);
                                if (!$school->logo) {
                                    $school->logo = $targetPath;
                                    $updated = true;
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {}
            }

            // 2. If base64 is missing, check disk or fallback to bundled repository logo
            if (empty($school->logo_base64)) {
                $restored = false;

                // Try reading from public disk if path was stored
                if (!empty($school->logo) && Storage::disk('public')->exists($school->logo)) {
                    try {
                        $mime = Storage::disk('public')->mimeType($school->logo) ?: 'image/png';
                        $content = Storage::disk('public')->get($school->logo);
                        $school->logo_base64 = 'data:' . $mime . ';base64,' . base64_encode($content);
                        $updated = true;
                        $restored = true;
                    } catch (\Throwable $e) {}
                }

                // If not restored from disk, seed bundled official Hande High logo
                if (!$restored && $bundledBase64 && $bundledBytes) {
                    $targetPath = "schools/{$school->id}/branding/hande-high-logo.png";
                    try {
                        Storage::disk('public')->put($targetPath, $bundledBytes);
                    } catch (\Throwable $e) {}

                    $school->logo = $targetPath;
                    $school->logo_base64 = $bundledBase64;
                    $updated = true;
                }
            }

            if ($updated) {
                $school->saveQuietly();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe down: do not delete school logos
    }
};
