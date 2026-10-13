<?php

namespace App\Rules;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class TenantExists implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @param string $table The table to check
     * @param string $column The column name, defaults to 'id'
     * @param string $tenantColumn The tenant column name, defaults to 'school_id'
     */
    public function __construct(
        private string $table,
        private string $column = 'id',
        private string $tenantColumn = 'school_id'
    ) {
    }

    /**
     * Convenient static constructor.
     */
    public static function make(string $table, string $column = 'id', string $tenantColumn = 'school_id'): self
    {
        return new self($table, $column, $tenantColumn);
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $tenant = app(TenantContext::class);

        if (! $tenant->hasTenant()) {
            $user = request()->user() ?? \Illuminate\Support\Facades\Auth::user();
            if ($user?->school) {
                $tenant->set($user->school);
            } elseif ($user?->school_id) {
                $school = \App\Models\School::find($user->school_id);
                if ($school) {
                    $tenant->set($school);
                }
            }
        }

        if (! $tenant->hasTenant()) {
            $user = request()->user() ?? \Illuminate\Support\Facades\Auth::user();
            if ($user && method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
                $exists = DB::table($this->table)->where($this->column, $value)->exists();
                if (! $exists) {
                    $fail("The selected {$attribute} is invalid.");
                }
                return;
            }

            $fail("The selected {$attribute} is invalid.");
            return;
        }

        $exists = DB::table($this->table)
            ->where($this->column, $value)
            ->where($this->tenantColumn, $tenant->id())
            ->exists();

        if (! $exists) {
            $fail("The selected {$attribute} is invalid.");
        }
    }
}
