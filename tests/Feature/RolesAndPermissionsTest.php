<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

it('keeps Super Admin out of approval, matching and payment authority', function () {
    $user = User::factory()->create()->assignRole('super_admin');

    expect($user->can('application.decide'))->toBeFalse()
        ->and($user->can('award.match-assign'))->toBeFalse()
        ->and($user->can('payment.record'))->toBeFalse()
        ->and($user->can('admin-access.manage'))->toBeTrue();
});

it('lets Admin approve, match and pay but not manage Admin access', function () {
    $user = User::factory()->create()->assignRole('admin');

    expect($user->can('application.decide'))->toBeTrue()
        ->and($user->can('award.match-assign'))->toBeTrue()
        ->and($user->can('payment.record'))->toBeTrue()
        ->and($user->can('donor.approve'))->toBeTrue()
        ->and($user->can('admin-access.manage'))->toBeFalse();
});

it('gives Students and Donors no operational authority', function (string $role) {
    $user = User::factory()->create()->assignRole($role);

    expect($user->can('application.decide'))->toBeFalse()
        ->and($user->can('payment.record'))->toBeFalse()
        ->and($user->can('award.match-assign'))->toBeFalse()
        ->and($user->can('admin-access.manage'))->toBeFalse();
})->with(['student', 'donor']);
