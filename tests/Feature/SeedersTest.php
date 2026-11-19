<?php

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds roles, reference data and one demo account per role', function () {
    $this->seed(DatabaseSeeder::class);

    foreach (['super_admin', 'admin', 'donor', 'student'] as $role) {
        expect(User::role($role)->count())->toBe(1);
    }

    $this->assertDatabaseHas('donor_profiles', ['display_name' => 'Demo Foundation', 'approval_status' => 'approved']);
    expect(DB::table('local_governments')->where('state_name', 'Gombe')->count())->toBe(11)
        ->and(DB::table('institutions')->count())->toBe(3);
});

it('can be run twice without duplicating anything', function () {
    $this->seed(DatabaseSeeder::class);
    $this->seed(DatabaseSeeder::class);

    expect(User::count())->toBe(4)
        ->and(DB::table('institutions')->count())->toBe(3)
        ->and(DB::table('programmes')->count())->toBe(7)
        ->and(DB::table('local_governments')->count())->toBe(11);
});
