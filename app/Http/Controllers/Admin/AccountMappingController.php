<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountMapping;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\Finance\FinancialAccountResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountMappingController extends Controller
{
    /** The vote keys in the approved Term 1 2026 proposal workbook. */
    public const FEE_VOTES = [
        'TUITION' => 'Tuition',
        'SCHOOL_LEVY' => 'School levy',
        'BUILDING' => 'Building fund',
        'SPORTS' => 'Sports',
        'ICT' => 'ICT',
        'PRACTICAL' => 'Practical',
        'GENERAL_FUND' => 'General fund',
        'BOARDING' => 'Boarding',
        'OTHER' => 'Other',
    ];

    public function index()
    {
        [$user, $school] = $this->authorizedSchoolAdmin();

        $mappings = AccountMapping::query()
            ->where('school_id', $school->id)
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->where('mapping_type', 'control_account')->where('source_key', 'ACCOUNTS_RECEIVABLE');
                })->orWhere(function ($q) {
                    $q->where('mapping_type', 'fee_vote')->whereIn('source_key', array_keys(self::FEE_VOTES));
                });
            })
            ->with('account')
            ->orderBy('mapping_type')
            ->orderBy('source_key')
            ->get();

        $accounts = Account::query()
            ->where('school_id', $school->id)
            ->where('is_active', true)
            ->where('is_postable', true)
            ->where('currency', 'USD')
            ->whereIn('type', ['asset', 'revenue'])
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'currency']);

        return view('admin.accounts.mappings', compact('mappings', 'accounts', 'school'));
    }

    public function store(Request $request, FinancialAccountResolver $resolver)
    {
        [$user, $school] = $this->authorizedSchoolAdmin();

        $validated = $request->validate([
            'mapping_type' => ['required', 'in:control_account,fee_vote'],
            'source_key' => ['required', 'string', 'max:100'],
            'account_id' => ['required', 'integer'],
            'finance_approval_reference' => ['required', 'string', 'min:4', 'max:150'],
            'finance_approval_confirmed' => ['required', 'accepted'],
        ]);

        $type = $validated['mapping_type'];
        $sourceKey = strtoupper(trim($validated['source_key']));
        $expectedAccountType = $type === 'control_account' ? 'asset' : 'revenue';
        $allowedSourceKeys = $type === 'control_account' ? ['ACCOUNTS_RECEIVABLE'] : array_keys(self::FEE_VOTES);

        if (! in_array($sourceKey, $allowedSourceKeys, true)) {
            throw ValidationException::withMessages(['source_key' => 'Select a supported receivable or Term 1 fee-vote key.']);
        }

        $account = Account::query()
            ->where('school_id', $school->id)
            ->whereKey($validated['account_id'])
            ->where('is_active', true)
            ->where('is_postable', true)
            ->where('currency', 'USD')
            ->where('type', $expectedAccountType)
            ->first();

        if (! $account) {
            throw ValidationException::withMessages(['account_id' => 'Choose an active, postable USD '.$expectedAccountType.' account belonging to '.$school->name.'.']);
        }

        $validated['finance_approval_reference'] = trim($validated['finance_approval_reference']);

        DB::transaction(function () use ($resolver, $school, $user, $account, $type, $sourceKey, $validated, $request) {
            $prior = AccountMapping::query()
                ->where('school_id', $school->id)
                ->where('mapping_type', $type)
                ->where('source_key', $sourceKey)
                ->lockForUpdate()
                ->first();

            $oldValues = $prior ? [
                'mapping_type' => $prior->mapping_type,
                'source_key' => $prior->source_key,
                'account_id' => $prior->account_id,
                'account_code' => $prior->account?->code,
                'is_active' => $prior->is_active,
            ] : null;

            $mapping = $resolver->setMapping(
                $school,
                $type,
                $sourceKey,
                $account,
                'Finance approval '.$validated['finance_approval_reference']
            );

            AuditLog::create([
                'school_id' => $school->id,
                'user_id' => $user->id,
                'action' => 'account_mapping_configured',
                'model_type' => AccountMapping::class,
                'model_id' => $mapping->id,
                'old_values' => $oldValues,
                'new_values' => [
                    'mapping_type' => $type,
                    'source_key' => $sourceKey,
                    'account_id' => $account->id,
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'account_type' => $account->type,
                    'currency' => $account->currency,
                    'finance_approval_reference' => $validated['finance_approval_reference'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'module' => 'finance',
                'description' => 'Configured '.$type.' mapping '.$sourceKey.' to '.$account->code.' using a recorded finance approval reference.',
            ]);
        });

        return redirect()->route('admin.account-mappings.index')->with('success', 'Approved account mapping saved and recorded in the audit log.');
    }

    /** @return array{0: User, 1: \App\Models\School} */
    private function authorizedSchoolAdmin(): array
    {
        $user = Auth::user();
        abort_unless($user instanceof User && $user->is_active && $user->hasAnyRole(['school-admin', 'super-admin']), 403);

        $school = $user->school;
        abort_unless($school, 403);

        return [$user, $school];
    }
}
