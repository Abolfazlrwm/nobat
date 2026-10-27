<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ([User::ROLE_SUPER_ADMIN, User::ROLE_MANAGER, User::ROLE_STAFF, User::ROLE_CUSTOMER] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
