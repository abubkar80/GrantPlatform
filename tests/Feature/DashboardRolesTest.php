<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->seed(RolesAndPermissionsSeeder::class));

function userWithRole(string $role): User
{
    return User::factory()->create()->assignRole($role);
}

function donorProfile(User $user, string $status = 'pending'): void
{
    DB::table('donor_profiles')->insert([
        'user_id' => $user->id,
        'display_name' => 'Test Donor',
        'approval_status' => $status,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

it('gives a student the student dashboard', function () {
    $student = userWithRole('student');
    DB::table('student_profiles')->insert(['user_id' => $student->id, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($student)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->component('dashboard')
        ->where('role', 'student')
        ->where('summary.applications', 0)
        ->where('summary.enrolment_status', 'pending'));
});

it('tells a pending donor they are awaiting approval, then shows approval', function () {
    $donor = userWithRole('donor');
    donorProfile($donor);

    $this->actingAs($donor)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('role', 'donor')
        ->where('summary.approval_status', 'pending'));

    DB::table('donor_profiles')->where('user_id', $donor->id)->update(['approval_status' => 'approved']);

    $this->actingAs($donor)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('summary.approval_status', 'approved'));
});

it('shows an Admin the donors waiting for approval', function () {
    donorProfile(userWithRole('donor'));
    donorProfile(userWithRole('donor'), 'approved');

    $this->actingAs(userWithRole('admin'))->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('role', 'admin')
        ->where('summary.pending_donors', 1)
        ->where('summary.awaiting_review', 0));
});

it('shows a Super Admin the account counts', function () {
    userWithRole('admin');
    userWithRole('student');
    userWithRole('student');

    $this->actingAs(userWithRole('super_admin'))->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('role', 'super_admin')
        ->where('summary.admins', 1)
        ->where('summary.students', 2));
});

it('uses the highest authority when an account holds several roles', function () {
    $user = userWithRole('donor')->assignRole('admin');

    $this->actingAs($user)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('role', 'admin'));
});

it('handles an account with no role', function () {
    $this->actingAs(User::factory()->create())->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('role', 'none'));
});
