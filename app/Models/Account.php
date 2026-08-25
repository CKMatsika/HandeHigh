<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Account extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'code',
        'name',
        'type',
        'category',
        'parent_id',
        'opening_balance',
        'currency',
        'is_active',
        'is_postable',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_active' => 'boolean',
        'is_postable' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function parent()
    {
        return $this->belongsTo(Account::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Account::class, 'parent_id')->orderBy('sort_order')->orderBy('code');
    }

    public function allChildren()
    {
        return $this->children()->with('allChildren');
    }

    public function journalEntries()
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function balances()
    {
        return $this->hasMany(AccountBalance::class);
    }

    public function budgetLines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function cashbookEntries()
    {
        return $this->hasMany(Cashbook::class, 'account_id');
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class, 'revenue_account_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'revenue_account_id');
    }

    public function kioskProducts()
    {
        return $this->hasMany(KioskProduct::class, 'revenue_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePostable($query)
    {
        return $query->where('is_postable', true);
    }

    public function scopeHeader($query)
    {
        return $query->where('is_postable', false);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Retrieve collection of ancestor accounts from direct parent up to root.
     */
    public function ancestors(): Collection
    {
        $ancestors = new Collection();
        $current = $this->parent;

        while ($current) {
            $ancestors->push($current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * Retrieve all descendant accounts (children, grandchildren, etc.).
     */
    public function descendants(): Collection
    {
        $descendants = new Collection();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->descendants());
        }

        return $descendants;
    }

    /**
     * Check if this account is a descendant of another account.
     */
    public function isDescendantOf(Account|int $account): bool
    {
        $targetId = $account instanceof Account ? $account->id : $account;
        return $this->ancestors()->contains('id', $targetId);
    }

    /**
     * Validate that assigning new parent does not introduce circular dependency.
     */
    public function validateNoHierarchyCycle(?int $newParentId): void
    {
        if ($newParentId === null) {
            return;
        }

        if ($newParentId === $this->id) {
            throw new InvalidArgumentException("Account cannot be its own parent.");
        }

        $parent = Account::find($newParentId);
        if ($parent && $parent->school_id !== $this->school_id) {
            throw new InvalidArgumentException("Parent account must belong to the same school tenant.");
        }

        if ($this->exists && $parent && $parent->isDescendantOf($this->id)) {
            throw new InvalidArgumentException("Circular hierarchy detected: account cannot have its own descendant as parent.");
        }
    }

    public function hasChildren(): bool
    {
        return $this->children()->exists();
    }

    public function hasJournalEntries(): bool
    {
        return $this->journalEntries()->exists();
    }

    public function canBeDeleted(): bool
    {
        return ! $this->hasJournalEntries() && ! $this->hasChildren();
    }

    public function getDepthAttribute(): int
    {
        return $this->ancestors()->count();
    }

    public function getIsLeafAttribute(): bool
    {
        return ! $this->hasChildren();
    }

    public function getIsRootAttribute(): bool
    {
        return $this->parent_id === null;
    }

    public function getCurrentBalanceAttribute(): float
    {
        // Calculate balance from journal entries
        $debits = (float) $this->journalEntries()->where('entry_type', 'debit')->sum('amount');
        $credits = (float) $this->journalEntries()->where('entry_type', 'credit')->sum('amount');
        
        // For assets and expenses: balance = debits - credits
        // For liabilities, equity, and revenue: balance = credits - debits
        if (in_array($this->type, ['asset', 'expense'])) {
            return $debits - $credits;
        }
        
        return $credits - $debits;
    }

    /**
     * Recursive tree balance including all descendant child balances.
     */
    public function getTreeBalanceAttribute(): float
    {
        $total = $this->current_balance;

        foreach ($this->children as $child) {
            $total += $child->tree_balance;
        }

        return $total;
    }

    public function getFormattedBalanceAttribute(): string
    {
        return number_format($this->current_balance, 2);
    }

    public function getFormattedTreeBalanceAttribute(): string
    {
        return number_format($this->tree_balance, 2);
    }
}
