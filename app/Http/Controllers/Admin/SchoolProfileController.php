<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Services\Branding\SchoolDocumentBrandingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
}
