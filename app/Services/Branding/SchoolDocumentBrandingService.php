<?php

namespace App\Services\Branding;

use App\Models\School;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SchoolDocumentBrandingService
{
    /**
     * Resolves the authoritative branding payload for a school,
     * merging with an optional historical snapshot if provided.
     */
    public function getBrandingPayload(School $school, ?array $snapshot = null): array
    {
        if (!empty($snapshot)) {
            $logoBase64 = !empty($snapshot['logo_base64']) ? $snapshot['logo_base64'] : $this->getLogoBase64($school);
            $rawLogoUrl = $snapshot['logo_url'] ?? null;
            if ($rawLogoUrl && !str_contains($rawLogoUrl, 'localhost') && !str_contains($rawLogoUrl, '127.0.0.1')) {
                $logoUrl = $rawLogoUrl;
            } else {
                $logoUrl = $this->getLogoUrl($school);
            }

            return [
                'name' => $snapshot['name'] ?? $school->name,
                'display_name' => $snapshot['display_name'] ?? $snapshot['name'] ?? $school->display_name ?? $school->name,
                'motto' => $snapshot['motto'] ?? $school->motto,
                'logo_url' => $logoUrl,
                'logo_base64' => $logoBase64,
                'has_logo' => !empty($logoBase64) || !empty($school->logo) || !empty($school->logo_base64),
                'address' => $snapshot['address'] ?? $school->address,
                'postal_address' => $snapshot['postal_address'] ?? $school->postal_address,
                'city' => $snapshot['city'] ?? $school->city,
                'state' => $snapshot['state'] ?? $school->state,
                'country' => $snapshot['country'] ?? $school->country,
                'postal_code' => $snapshot['postal_code'] ?? $school->postal_code,
                'formatted_address' => $this->formatAddress($snapshot, $school),
                'phone' => $snapshot['phone'] ?? $school->phone,
                'telephone' => $snapshot['telephone'] ?? $school->telephone,
                'mobile' => $snapshot['mobile'] ?? $school->mobile,
                'whatsapp' => $snapshot['whatsapp'] ?? $school->whatsapp,
                'formatted_contacts' => $this->formatContacts($snapshot, $school),
                'email' => $snapshot['email'] ?? $school->email,
                'website' => $snapshot['website'] ?? $school->website,
                'registration_number' => $snapshot['registration_number'] ?? $school->registration_number,
                'zimsec_center_number' => $snapshot['zimsec_center_number'] ?? $school->zimsec_center_number,
                'principal_name' => $snapshot['principal_name'] ?? $school->principal_name,
                'bursar_name' => $snapshot['bursar_name'] ?? $school->bursar_name,
                'administrator_name' => $snapshot['administrator_name'] ?? $school->administrator_name,
                'primary_color' => $snapshot['primary_color'] ?? $school->primary_color ?? '#1e3a8a',
                'secondary_color' => $snapshot['secondary_color'] ?? $school->secondary_color ?? '#d97706',
                'footer_text' => $snapshot['footer_text'] ?? $school->footer_text ?? 'Excellence in Education',
                'bank_name' => $snapshot['bank_name'] ?? $school->bank_name,
                'bank_account_name' => $snapshot['bank_account_name'] ?? $school->bank_account_name,
                'bank_account_number' => $snapshot['bank_account_number'] ?? $school->bank_account_number,
                'bank_branch' => $snapshot['bank_branch'] ?? $school->bank_branch,
                'payment_instructions' => $snapshot['payment_instructions'] ?? $school->payment_instructions,
                'is_historical_snapshot' => true,
                'snapshot_date' => $snapshot['snapshot_date'] ?? null,
            ];
        }

        $logoBase64 = $this->getLogoBase64($school);
        $logoUrl = $this->getLogoUrl($school);

        return [
            'name' => $school->name,
            'display_name' => $school->display_name ?: $school->name,
            'motto' => $school->motto,
            'logo_url' => $logoUrl,
            'logo_base64' => $logoBase64,
            'has_logo' => !empty($logoBase64) || !empty($school->logo) || !empty($school->logo_base64),
            'address' => $school->address,
            'postal_address' => $school->postal_address,
            'city' => $school->city,
            'state' => $school->state,
            'country' => $school->country,
            'postal_code' => $school->postal_code,
            'formatted_address' => $this->formatAddress(null, $school),
            'phone' => $school->phone,
            'telephone' => $school->telephone,
            'mobile' => $school->mobile,
            'whatsapp' => $school->whatsapp,
            'formatted_contacts' => $this->formatContacts(null, $school),
            'email' => $school->email,
            'website' => $school->website,
            'registration_number' => $school->registration_number,
            'zimsec_center_number' => $school->zimsec_center_number,
            'principal_name' => $school->principal_name,
            'bursar_name' => $school->bursar_name,
            'administrator_name' => $school->administrator_name,
            'primary_color' => $school->primary_color ?: '#1e3a8a',
            'secondary_color' => $school->secondary_color ?: '#d97706',
            'footer_text' => $school->footer_text ?: 'Excellence in Education',
            'bank_name' => $school->bank_name,
            'bank_account_name' => $school->bank_account_name,
            'bank_account_number' => $school->bank_account_number,
            'bank_branch' => $school->bank_branch,
            'payment_instructions' => $school->payment_instructions,
            'is_historical_snapshot' => false,
            'snapshot_date' => null,
        ];
    }

    /**
     * Generates a frozen snapshot array of the school's profile at issuance time.
     */
    public function generateSnapshot(School $school): array
    {
        return [
            'name' => $school->name,
            'display_name' => $school->display_name ?: $school->name,
            'motto' => $school->motto,
            'logo_url' => $this->getLogoUrl($school),
            'logo_base64' => $this->getLogoBase64($school),
            'address' => $school->address,
            'postal_address' => $school->postal_address,
            'city' => $school->city,
            'state' => $school->state,
            'country' => $school->country,
            'postal_code' => $school->postal_code,
            'phone' => $school->phone,
            'telephone' => $school->telephone,
            'mobile' => $school->mobile,
            'whatsapp' => $school->whatsapp,
            'email' => $school->email,
            'website' => $school->website,
            'registration_number' => $school->registration_number,
            'zimsec_center_number' => $school->zimsec_center_number,
            'principal_name' => $school->principal_name,
            'bursar_name' => $school->bursar_name,
            'administrator_name' => $school->administrator_name,
            'primary_color' => $school->primary_color ?: '#1e3a8a',
            'secondary_color' => $school->secondary_color ?: '#d97706',
            'footer_text' => $school->footer_text,
            'bank_name' => $school->bank_name,
            'bank_account_name' => $school->bank_account_name,
            'bank_account_number' => $school->bank_account_number,
            'bank_branch' => $school->bank_branch,
            'payment_instructions' => $school->payment_instructions,
            'snapshot_date' => now()->toIso8601String(),
        ];
    }

    /**
     * Store an uploaded logo safely into the tenant-specific directory and database base64.
     */
    public function storeLogo(School $school, UploadedFile $file): string
    {
        $this->deleteLogo($school);

        $filename = 'logo_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs("schools/{$school->id}/branding", $filename, 'public');

        $mime = $file->getMimeType() ?: 'image/png';
        $content = file_get_contents($file->getRealPath());
        $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

        $school->update([
            'logo' => $path,
            'logo_base64' => $base64,
        ]);

        return $path;
    }

    /**
     * Delete the school's logo safely.
     */
    public function deleteLogo(School $school): void
    {
        if ($school->logo && Storage::disk('public')->exists($school->logo)) {
            Storage::disk('public')->delete($school->logo);
        }

        $school->update([
            'logo' => null,
            'logo_base64' => null,
        ]);
    }

    /**
     * Get the public URL for the school logo using dedicated streaming route.
     */
    public function getLogoUrl(School $school): ?string
    {
        if (!$school->id) {
            return null;
        }

        // Return the dedicated streaming endpoint for this school
        // This ensures proper caching, automatic SVG crest fallback if missing, and immunity to APP_URL misconfigurations.
        return url("/schools/{$school->id}/logo");
    }

    /**
     * Get the base64 data URI of the logo for offline-safe print rendering.
     */
    public function getLogoBase64(School $school): ?string
    {
        // 1. Direct from database (fastest, persists across cloud container redeployments)
        $rawBase64 = $school->getAttributes()['logo_base64'] ?? null;
        if (!empty($rawBase64)) {
            $this->ensureFileOnDiskFromBase64($school);
            return $rawBase64;
        }

        // 2. Check public storage disk if path exists
        if ($school->logo) {
            try {
                if (Storage::disk('public')->exists($school->logo)) {
                    $mime = Storage::disk('public')->mimeType($school->logo) ?: 'image/png';
                    $content = Storage::disk('public')->get($school->logo);
                    $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

                    // Backfill into database so it persists across container redeployments
                    if ($school->id) {
                        try {
                            $school->updateQuietly(['logo_base64' => $base64]);
                        } catch (\Throwable $e) {}
                    }

                    return $base64;
                }
            } catch (\Throwable $e) {
                // Fallback to direct path checks below
            }

            // 3. Fallback: check standard storage paths on local filesystem
            $candidatePaths = [
                storage_path('app/public/' . ltrim($school->logo, '/')),
                public_path('storage/' . ltrim($school->logo, '/')),
                public_path(ltrim($school->logo, '/')),
            ];

            foreach ($candidatePaths as $path) {
                if (file_exists($path)) {
                    try {
                        $mime = mime_content_type($path) ?: 'image/png';
                        $content = file_get_contents($path);
                        $base64 = 'data:' . $mime . ';base64,' . base64_encode($content);

                        if ($school->id) {
                            try {
                                $school->updateQuietly(['logo_base64' => $base64]);
                            } catch (\Throwable $e) {}
                        }

                        return $base64;
                    } catch (\Throwable $e) {
                        // Continue
                    }
                }
            }
        }

        // 4. Bundled official school logo in repository for Hande High School (ensures documents never lose the logo)
        $isHandeHigh = ($school->code === 'HHS') || str_contains(strtolower($school->name ?? ''), 'hande');
        $bundledLogoPath = public_path('images/hande-high-logo.png');
        if ($isHandeHigh && file_exists($bundledLogoPath)) {
            try {
                $content = file_get_contents($bundledLogoPath);
                $base64 = 'data:image/png;base64,' . base64_encode($content);

                if ($school->id && empty($school->logo)) {
                    try {
                        $targetPath = "schools/{$school->id}/branding/hande-high-logo.png";
                        Storage::disk('public')->put($targetPath, $content);
                        $school->updateQuietly([
                            'logo' => $targetPath,
                            'logo_base64' => $base64,
                        ]);
                    } catch (\Throwable $e) {}
                }

                return $base64;
            } catch (\Throwable $e) {}
        }

        return null;
    }

    /**
     * Rehydrate the physical file on disk from the database base64 string.
     * Guarantees that even after ephemeral container redeployment, file storage matches the database.
     */
    public function ensureFileOnDiskFromBase64(School $school, ?string $mimeType = null, ?string $binaryData = null): void
    {
        $rawBase64 = $school->getAttributes()['logo_base64'] ?? $school->logo_base64;
        if (empty($rawBase64)) {
            return;
        }

        try {
            $expectedPath = $school->logo ?: ("schools/{$school->id}/branding/logo.png");
            if (!Storage::disk('public')->exists($expectedPath)) {
                if ($binaryData === null) {
                    if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.+)$/', $rawBase64, $matches)) {
                        $binaryData = base64_decode($matches[2]);
                    }
                }
                if ($binaryData) {
                    Storage::disk('public')->put($expectedPath, $binaryData);
                    if (!$school->logo) {
                        $school->updateQuietly(['logo' => $expectedPath]);
                    }
                }
            }
        } catch (\Throwable $e) {
            // Non-blocking
        }
    }

    /**
     * Helper to format physical and postal address.
     */
    protected function formatAddress(?array $snapshot, School $school): string
    {
        $address = $snapshot['address'] ?? $school->address;
        $city = $snapshot['city'] ?? $school->city;
        $state = $snapshot['state'] ?? $school->state;
        $country = $snapshot['country'] ?? $school->country;

        $parts = array_filter([$address, $city, $state, $country]);
        return implode(', ', $parts);
    }

    /**
     * Helper to format contact line (phone, mobile, whatsapp, email).
     */
    protected function formatContacts(?array $snapshot, School $school): string
    {
        $tel = $snapshot['telephone'] ?? $school->telephone ?: ($snapshot['phone'] ?? $school->phone);
        $mob = $snapshot['mobile'] ?? $school->mobile;
        $wa = $snapshot['whatsapp'] ?? $school->whatsapp;
        $email = $snapshot['email'] ?? $school->email;

        $parts = [];
        if ($tel) {
            $parts[] = "Tel: {$tel}";
        }
        if ($mob && $mob !== $tel) {
            $parts[] = "Mobile: {$mob}";
        }
        if ($wa && $wa !== $mob && $wa !== $tel) {
            $parts[] = "WhatsApp: {$wa}";
        }
        if ($email) {
            $parts[] = "Email: {$email}";
        }

        return implode(' | ', $parts);
    }
}
