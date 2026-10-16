<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\Branding\SchoolDocumentBrandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SchoolProfileController extends Controller
{
    public function __construct(
        protected SchoolDocumentBrandingService $brandingService
    ) {}

    /**
     * Show the school profile & branding edit form for the active tenant.
     */
    public function edit()
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403, 'Unauthorized. Active school tenant required.');
        }

        $branding = $this->brandingService->getBrandingPayload($school);

        return view('admin.school.profile', compact('school', 'branding'));
    }

    /**
     * Update the school profile & branding configuration.
     */
    public function update(Request $request)
    {
        $school = Auth::user()?->school;
        if (! $school) {
            abort(403, 'Unauthorized. Active school tenant required.');
        }

        $validated = $request->validate([
            // 1. Identity
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'motto' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'established_year' => ['nullable', 'integer', 'min:1800', 'max:' . (date('Y') + 1)],
            'school_type' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'zimsec_center_number' => ['nullable', 'string', 'max:100'],

            // 2. Contacts & Address
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'whatsapp' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'postal_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'website' => ['nullable', 'string', 'max:255'],

            // 3. Administration
            'principal_name' => ['nullable', 'string', 'max:255'],
            'bursar_name' => ['nullable', 'string', 'max:255'],
            'administrator_name' => ['nullable', 'string', 'max:255'],

            // 4. Branding & Aesthetics
            'primary_color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'string', 'max:20', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'footer_text' => ['nullable', 'string', 'max:500'],

            // 5. Logo Management (Security: image mime check, no svg, max 2MB)
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],

            // 6. Financial & Payment Instructions
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'payment_instructions' => ['nullable', 'string', 'max:1000'],
        ]);

        // Handle logo removal
        if ($request->boolean('remove_logo')) {
            $this->brandingService->deleteLogo($school);
        }

        // Handle new logo upload
        if ($request->hasFile('logo')) {
            $this->brandingService->storeLogo($school, $request->file('logo'));
        }

        // Remove logo fields from general update array
        unset($validated['logo'], $validated['remove_logo']);

        $school->update($validated);

        return redirect()->route('admin.school.profile')
            ->with('success', 'School profile and document branding updated successfully.')
            ->with('status', 'School profile and document branding updated successfully.');
    }

    /**
     * Dedicated streaming endpoint for school logo.
     * Guaranteed to never 404: decodes base64, rehydrates disk, falls back to bundled repository logo,
     * or dynamically renders an official heraldic SVG crest.
     */
    public function streamLogo(School $school)
    {
        // 1. Direct from base64 stored in database (persists across ephemeral container deployments)
        $rawBase64 = $school->getAttributes()['logo_base64'] ?? $school->logo_base64;
        if (!empty($rawBase64)) {
            if (preg_match('/^data:(image\/[a-zA-Z0-9\+\-\.]+);base64,(.+)$/', $rawBase64, $matches)) {
                $mimeType = $matches[1];
                $data = base64_decode($matches[2]);
                if ($data !== false) {
                    $this->brandingService->ensureFileOnDiskFromBase64($school, $mimeType, $data);

                    return response($data, 200, [
                        'Content-Type' => $mimeType,
                        'Content-Length' => strlen($data),
                        'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
                        'ETag' => '"' . md5($rawBase64) . '"',
                    ]);
                }
            }
        }

        // 2. Check public storage disk
        if ($school->logo && Storage::disk('public')->exists($school->logo)) {
            $content = Storage::disk('public')->get($school->logo);
            $mimeType = Storage::disk('public')->mimeType($school->logo) ?: 'image/png';

            return response($content, 200, [
                'Content-Type' => $mimeType,
                'Content-Length' => strlen($content),
                'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            ]);
        }

        // 3. Check permanent repository bundled logo for Hande High School
        $isHandeHigh = ($school->code === 'HHS') || str_contains(strtolower($school->name ?? ''), 'hande');
        $bundledLogoPath = public_path('images/hande-high-logo.png');
        if ($isHandeHigh && file_exists($bundledLogoPath)) {
            $content = file_get_contents($bundledLogoPath);
            return response($content, 200, [
                'Content-Type' => 'image/png',
                'Content-Length' => strlen($content),
                'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
            ]);
        }

        // 4. Dynamic Heraldic SVG crest fallback
        $primaryColor = $school->primary_color ?: '#0b1e36';
        $secondaryColor = $school->secondary_color ?: '#d97706';
        $name = $school->display_name ?: $school->name ?: 'School';
        $words = preg_split('/\s+/', trim($name));
        $initials = '';
        foreach (array_slice($words, 0, 2) as $w) {
            $initials .= strtoupper(substr($w, 0, 1));
        }
        if (empty($initials)) {
            $initials = 'HH';
        }

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="200" height="200">
    <defs>
        <linearGradient id="shieldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="{$primaryColor}" />
            <stop offset="100%" stop-color="#020617" />
        </linearGradient>
        <linearGradient id="goldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#fbbf24" />
            <stop offset="100%" stop-color="{$secondaryColor}" />
        </linearGradient>
    </defs>
    <circle cx="100" cy="100" r="92" fill="url(#shieldGrad)" stroke="url(#goldGrad)" stroke-width="6" />
    <circle cx="100" cy="100" r="82" fill="none" stroke="url(#goldGrad)" stroke-width="1.5" stroke-dasharray="4 2" />
    <path d="M 100 35 Q 145 35 155 70 Q 155 130 100 165 Q 45 130 45 70 Q 55 35 100 35 Z" fill="#0f172a" stroke="url(#goldGrad)" stroke-width="3" opacity="0.9" />
    <text x="100" y="112" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="38" font-weight="900" fill="#ffffff" text-anchor="middle" letter-spacing="1">{$initials}</text>
    <rect x="58" y="126" width="84" height="18" rx="9" fill="url(#goldGrad)" />
    <text x="100" y="139" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif" font-size="9" font-weight="800" fill="#0f172a" text-anchor="middle" letter-spacing="1.5">EXCELLENCE</text>
</svg>
SVG;

        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Cache-Control' => 'public, max-age=86400, stale-while-revalidate=604800',
        ]);
    }
}
