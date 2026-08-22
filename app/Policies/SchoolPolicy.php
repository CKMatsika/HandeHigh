<?php

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super-admin');
    }

    public function update(User $user, School $school): bool
    {
        return $user->hasRole('super-admin');
    }

    public function delete(User $user, School $school): bool
    {
        return $user->hasRole('super-admin');
    }
}
