<?php

namespace App\Services\Finance;

use App\Models\Account;
use App\Models\School;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChartOfAccountsService
{
    /**
     * Build the hierarchical tree of accounts for a given school.
     * Eagerly loads multi-level children to avoid N+1 queries.
     *
     * @param School|int $school
     * @param array $filters
     * @return Collection
     */
    public function getAccountTree(School|int $school, array $filters = []): Collection
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        $query = Account::where('school_id', $schoolId);

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['is_postable']) && $filters['is_postable'] !== '') {
            $query->where('is_postable', (bool) $filters['is_postable']);
        }

        if (! empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Fetch all matching accounts sorted logically
        $allAccounts = $query->with(['parent'])
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        // Build in-memory tree map for optimal O(N) performance
        $grouped = $allAccounts->groupBy('parent_id');

        $buildSubtree = function ($parentId) use (&$buildSubtree, $grouped) {
            $children = $grouped->get($parentId, collect());

            foreach ($children as $child) {
                $child->setRelation('children', $buildSubtree($child->id));
            }

            return $children;
        };

        // If search filter is active and parent is filtered out, return top-level matches
        if (! empty($filters['search']) || ! empty($filters['type'])) {
            $rootIds = $allAccounts->pluck('id')->all();
            $roots = $allAccounts->filter(function ($acc) use ($rootIds) {
                return $acc->parent_id === null || ! in_array($acc->parent_id, $rootIds);
            });

            foreach ($roots as $root) {
                $root->setRelation('children', $buildSubtree($root->id));
            }

            return $roots->values();
        }

        return $buildSubtree(null)->values();
    }

    /**
     * Get chart of accounts summary statistics for dashboard.
     */
    public function getStatistics(School|int $school): array
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        $accounts = Account::where('school_id', $schoolId)->get();

        return [
            'total_accounts' => $accounts->count(),
            'active_accounts' => $accounts->where('is_active', true)->count(),
            'inactive_accounts' => $accounts->where('is_active', false)->count(),
            'header_accounts' => $accounts->where('is_postable', false)->count(),
            'postable_accounts' => $accounts->where('is_postable', true)->count(),
            'by_type' => [
                'asset' => $accounts->where('type', 'asset')->count(),
                'liability' => $accounts->where('type', 'liability')->count(),
                'equity' => $accounts->where('type', 'equity')->count(),
                'revenue' => $accounts->where('type', 'revenue')->count(),
                'expense' => $accounts->where('type', 'expense')->count(),
            ],
        ];
    }

    /**
     * Safely create a new account or subaccount with hierarchy validation.
     */
    public function createAccount(School|int $school, array $data): Account
    {
        $schoolId = $school instanceof School ? $school->id : $school;

        $parentId = ! empty($data['parent_id']) ? (int) $data['parent_id'] : null;

        if ($parentId) {
            $parent = Account::where('school_id', $schoolId)->find($parentId);
            if (! $parent) {
                throw new InvalidArgumentException("Parent account not found in this school.");
            }
            // If type or category not explicitly provided, inherit from parent
            $data['type'] = $data['type'] ?? $parent->type;
            $data['category'] = $data['category'] ?? $parent->category;
        }

        $existing = Account::where('school_id', $schoolId)->where('code', $data['code'])->first();
        if ($existing) {
            throw new InvalidArgumentException("Account code [{$data['code']}] already exists for this school.");
        }

        return Account::create(array_merge($data, [
            'school_id' => $schoolId,
            'is_active' => $data['is_active'] ?? true,
            'is_postable' => $data['is_postable'] ?? true,
            'opening_balance' => $data['opening_balance'] ?? 0.00,
            'currency' => $data['currency'] ?? 'USD',
        ]));
    }

    /**
     * Safely update an existing account with cycle detection.
     */
    public function updateAccount(Account $account, array $data): Account
    {
        $newParentId = array_key_exists('parent_id', $data) ? ($data['parent_id'] ? (int) $data['parent_id'] : null) : $account->parent_id;

        $account->validateNoHierarchyCycle($newParentId);

        if (! empty($data['code']) && $data['code'] !== $account->code) {
            $existing = Account::where('school_id', $account->school_id)
                ->where('code', $data['code'])
                ->where('id', '!=', $account->id)
                ->first();

            if ($existing) {
                throw new InvalidArgumentException("Account code [{$data['code']}] is already in use by another account.");
            }
        }

        $account->update($data);

        return $account->fresh(['parent', 'children']);
    }

    /**
     * Delete an account safely or throw exception if protected by financial history or child hierarchy.
     */
    public function deleteAccount(Account $account): bool
    {
        if ($account->hasJournalEntries()) {
            throw new InvalidArgumentException("Cannot delete account [{$account->code}] because it contains historical general ledger transactions. Deactivate it instead.");
        }

        if ($account->hasChildren()) {
            throw new InvalidArgumentException("Cannot delete account [{$account->code}] because it has child subaccounts. Reassign or delete child accounts first.");
        }

        return (bool) $account->delete();
    }
}
