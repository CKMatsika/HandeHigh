<?php

namespace App\Support\Tenancy;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class TenantContext
{
    private ?School $school = null;

    public function set(School $school): void
    {
        $this->school = $school;
    }

    public function hasTenant(): bool
    {
        return $this->school !== null;
    }

    public function school(): ?School
    {
        return $this->school;
    }

    public function id(): int
    {
        if (! $this->school) {
            throw new LogicException('A tenant has not been resolved.');
        }

        return (int) $this->school->getKey();
    }

    public function requireTenant(): School
    {
        if (! $this->school) {
            throw new LogicException('A tenant has not been resolved.');
        }

        return $this->school;
    }

    public function constrain(Builder $query): Builder
    {
        return $query->where($query->getModel()->getTable() . '.school_id', $this->id());
    }

    public function owns(Model $model): bool
    {
        return $this->hasTenant()
            && (int) $model->getAttribute('school_id') === $this->id();
    }
}
