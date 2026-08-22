<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public static function roles(): array
    {
        return [
            'super-admin',
            'school-admin',
            'headmaster',
            'deputy-headmaster',
            'accountant',
            'accounts-clerk',
            'bursar',
            'procurement-officer',
            'teacher',
            'student',
            'parent',
            'librarian',
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = config('auth.defaults.guard', 'web');

        foreach (self::roles() as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => $guard,
            ]);
        }
    }
}
