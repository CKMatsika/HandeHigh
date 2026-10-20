<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventorySetting;
use App\Models\School;
use Illuminate\Http\Request;

class InventorySettingsController extends Controller
{
    public function edit()
    {
        $school = $this->school();
        $setting = InventorySetting::firstOrNew(['school_id' => $school->id]);

        return view('admin.inventory.settings', compact('setting'));
    }

    public function update(Request $request)
    {
        $school = $this->school();
        $validated = $request->validate([
            'asset_capitalization_threshold' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);
        $setting = InventorySetting::firstOrNew(['school_id' => $school->id]);
        $setting->valuation_method ??= 'weighted_average';
        $setting->asset_capitalization_threshold = $validated['asset_capitalization_threshold'] ?? null;
        $setting->save();

        return redirect()->route('admin.inventory.settings.edit')
            ->with('success', 'Asset capitalization settings updated.');
    }

    private function school(): School
    {
        $user = auth()->user();
        $school = $user?->school ?? ($user?->hasRole('super-admin') ? School::first() : null);
        abort_unless($school, 403);

        return $school;
    }
}
