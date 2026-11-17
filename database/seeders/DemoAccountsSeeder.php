<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * One demo account per role so every portal can be shown without registering.
 * Local/demo use only: it refuses to run in production.
 */
class DemoAccountsSeeder extends Seeder
{
    public const PASSWORD = 'Demo-Passw0rd!';

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $accounts = [
            'super_admin' => ['superadmin@grantplatform.test', 'Demo Super Admin'],
            'admin' => ['admin@grantplatform.test', 'Demo Admin'],
            'donor' => ['donor@grantplatform.test', 'Demo Foundation'],
            'student' => ['student@grantplatform.test', 'Demo Student'],
        ];

        $users = [];
        foreach ($accounts as $role => [$email, $name]) {
            $users[$role] = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => self::PASSWORD],
            );
            $users[$role]->syncRoles([$role]);
        }

        $now = now();

        DB::table('donor_profiles')->updateOrInsert(
            ['user_id' => $users['donor']->id],
            [
                'display_name' => 'Demo Foundation',
                'approval_status' => 'approved',
                'reviewed_by' => $users['admin']->id,
                'reviewed_at' => $now,
                'review_reason' => 'Seeded demo account',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );

        DB::table('student_profiles')->updateOrInsert(
            ['user_id' => $users['student']->id],
            ['created_at' => $now, 'updated_at' => $now],
        );
    }
}
