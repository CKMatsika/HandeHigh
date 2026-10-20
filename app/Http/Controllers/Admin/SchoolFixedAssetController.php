<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\SchoolFixedAsset;
use App\Services\SchoolFixedAssetService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SchoolFixedAssetController extends Controller
{
    public function index()
    {
        $school = $this->school();
        $assets = SchoolFixedAsset::where('school_id', $school->id)
            ->with(['item', 'warehouse', 'custodian', 'assetAccount'])
            ->latest('acquired_date')
            ->paginate(25);

        return view('admin.inventory.fixed-assets.index', compact('assets'));
    }

    public function show(SchoolFixedAsset $fixedAsset)
    {
        $this->assertSchoolRecord($fixedAsset);
        $fixedAsset->load([
            'item.category',
            'warehouse',
            'custodian',
            'assetAccount',
            'goodsReceivedItem.voucher',
            'supplierInvoiceItem.supplierInvoice.vendor',
            'assignments.fromUser',
            'assignments.toUser',
            'assignments.assignedBy',
        ]);
        $users = $this->school()->users()->orderBy('name')->get();

        return view('admin.inventory.fixed-assets.show', compact('fixedAsset', 'users'));
    }

    public function updateCustody(
        Request $request,
        SchoolFixedAsset $fixedAsset,
        SchoolFixedAssetService $assetService
    ) {
        $this->assertSchoolRecord($fixedAsset);
        $school = $this->school();
        $validated = $request->validate([
            'custodian_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('school_id', $school->id),
            ],
            'serial_number' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('school_fixed_assets', 'serial_number')
                    ->where('school_id', $school->id)
                    ->ignore($fixedAsset->id),
            ],
            'location' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $custodian = isset($validated['custodian_user_id'])
            ? $school->users()->findOrFail($validated['custodian_user_id'])
            : null;

        $assetService->updateCustody(
            $fixedAsset,
            $custodian,
            $validated['serial_number'] ?? '',
            $validated['location'],
            $validated['notes'] ?? null,
            $request->user()
        );

        return back()->with('success', 'Asset details and custody updated.');
    }

    private function school(): School
    {
        $user = auth()->user();
        $school = $user?->school ?? ($user?->hasRole('super-admin') ? School::first() : null);
        abort_unless($school, 403);

        return $school;
    }

    private function assertSchoolRecord(SchoolFixedAsset $fixedAsset): void
    {
        abort_unless((int) $fixedAsset->school_id === (int) $this->school()->id, 404);
    }
}
