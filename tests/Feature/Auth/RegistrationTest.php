<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function registrationData(array $overrides = []): array
{
    return array_merge([
        'name' => 'Amina Bello',
        'email' => 'amina@example.com',
        'password' => 'Str0ng-Passw0rd!',
        'password_confirmation' => 'Str0ng-Passw0rd!',
        'account_type' => 'student',
        'consent' => 'on',
    ], $overrides);
}

it('renders the registration screen', function () {
    $this->get('/register')->assertOk();
});

it('registers a student with a role, an empty profile and a consent record', function () {
    $this->post('/register', registrationData())->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $user = User::where('email', 'amina@example.com')->firstOrFail();

    expect($user->hasRole('student'))->toBeTrue()->and($user->hasRole('donor'))->toBeFalse();
    $this->assertDatabaseHas('student_profiles', ['user_id' => $user->id, 'enrolment_status' => 'pending']);
    $this->assertDatabaseHas('consent_events', ['user_id' => $user->id, 'action' => 'granted', 'scope' => 'data_use']);
});

it('registers a donor as pending Admin approval', function () {
    $this->post('/register', registrationData(['account_type' => 'donor']))->assertRedirect(route('dashboard'));

    $user = User::where('email', 'amina@example.com')->firstOrFail();

    expect($user->hasRole('donor'))->toBeTrue();
    $this->assertDatabaseHas('donor_profiles', [
        'user_id' => $user->id,
        'display_name' => 'Amina Bello',
        'approval_status' => 'pending',
    ]);
    $this->assertDatabaseMissing('student_profiles', ['user_id' => $user->id]);
});

it('never lets public registration create privileged accounts', function (string $type) {
    $this->post('/register', registrationData(['account_type' => $type]))
        ->assertSessionHasErrors('account_type');

    $this->assertGuest();
    $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
})->with(['admin', 'super_admin', 'Admin', 'SUPER_ADMIN', '']);

it('ignores client-supplied role fields', function () {
    $this->post('/register', registrationData(['role' => 'admin', 'roles' => ['admin', 'super_admin']]));

    $user = User::where('email', 'amina@example.com')->firstOrFail();

    expect($user->getRoleNames()->all())->toBe(['student']);
});

it('requires consent to data use', function () {
    $this->post('/register', registrationData(['consent' => null]))->assertSessionHasErrors('consent');

    $this->assertDatabaseMissing('users', ['email' => 'amina@example.com']);
});

it('rejects a duplicate email regardless of case', function () {
    User::factory()->create(['email' => 'amina@example.com']);

    $this->post('/register', registrationData(['email' => 'AMINA@example.com']))->assertSessionHasErrors('email');
});

it('rejects a mismatched password confirmation', function () {
    $this->post('/register', registrationData(['password_confirmation' => 'something-else']))
        ->assertSessionHasErrors('password');
});
