<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Four roles and the permission matrix from SRS section 2.
 * Super Admin = oversight and Admin-access management only: no approval, matching or payment authority.
 * Roles are never granted by public registration; Student and Donor are assigned by the server on sign-up.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $matrix = [
            'student' => [
                'application.edit-own',
                'flag.view-own',
            ],
            'donor' => [
                'fund.set-criteria-own',
                'flag.view-anonymous',
            ],
            'admin' => [
                'application.review',
                'application.decide',
                'fund.view-for-matching',
                'donor.approve',
                'award.match-assign',
                'payment.record',
                'flag.decide',
                'audit.view-authorized',
            ],
            'super_admin' => [
                'application.oversee',
                'fund.oversee',
                'flag.oversee',
                'admin-access.manage',
                'audit.view-platform',
            ],
        ];

        foreach (collect($matrix)->flatten()->unique() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // WithoutModelEvents (used by DatabaseSeeder) stops Spatie clearing its cache itself,
        // so clear it here before roles look the new permissions up.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($matrix as $role => $permissions) {
            Role::findOrCreate($role, 'web')->syncPermissions($permissions);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
