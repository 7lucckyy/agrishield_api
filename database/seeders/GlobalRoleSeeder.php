<?php

namespace Database\Seeders;

use App\Enums\GlobalRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class GlobalRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (GlobalRole::cases() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }
}
