<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->withoutVite();
});

/** @return array{0: User, 1: int} the donor user and the donor_profiles id */
function makePendingDonor(string $name = 'Acme Foundation'): array
{
    $user = User::factory()->create()->assignRole('donor');
    $id = DB::table('donor_profiles')->insertGetId([
        'user_id' => $user->id,
        'display_name' => $name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return [$user, $id];
}

function adminUser(): User
{
    return User::factory()->create()->assignRole('admin');
}

it('lists pending donors for an Admin', function () {
    makePendingDonor('Pending One');
    [$approved, $approvedId] = makePendingDonor('Already Approved');
    DB::table('donor_profiles')->where('id', $approvedId)->update(['approval_status' => 'approved']);

    $this->actingAs(adminUser())->get('/admin/donors')->assertInertia(fn (Assert $page) => $page
        ->component('admin/donors')
        ->where('filter', 'pending')
        ->has('donors', 1)
        ->where('donors.0.display_name', 'Pending One')
        ->where('counts.pending', 1)
        ->where('counts.approved', 1));
});

it('keeps everyone except Admins out', function (string $role) {
    [, $id] = makePendingDonor();

    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)->get('/admin/donors')->assertForbidden();
    $this->actingAs($user)->post("/admin/donors/{$id}/approve")->assertForbidden();

    expect(DB::table('donor_profiles')->where('id', $id)->value('approval_status'))->toBe('pending');
})->with(['student', 'donor', 'super_admin']);

it('sends guests to the login page', function () {
    $this->get('/admin/donors')->assertRedirect('/login');
});

it('approves a donor, records the audit trail and notifies the donor', function () {
    [$donor, $id] = makePendingDonor();
    $admin = adminUser();

    $this->actingAs($admin)->post("/admin/donors/{$id}/approve")->assertSessionHas('status');

    $profile = DB::table('donor_profiles')->where('id', $id)->first();
    expect($profile->approval_status)->toBe('approved')
        ->and($profile->reviewed_by)->toBe($admin->id);

    $this->assertDatabaseHas('audit_events', [
        'actor_id' => $admin->id,
        'action' => 'donor.approved',
        'subject_type' => 'donor_profile',
        'subject_id' => $id,
        'outcome' => 'success',
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $donor->id,
        'kind' => 'donor.registration.approved',
    ]);
});

it('declines a donor only with a reason', function () {
    [, $id] = makePendingDonor();
    $admin = adminUser();

    $this->actingAs($admin)->post("/admin/donors/{$id}/decline", ['reason' => ''])
        ->assertSessionHasErrors('reason');
    expect(DB::table('donor_profiles')->where('id', $id)->value('approval_status'))->toBe('pending');

    $this->actingAs($admin)->post("/admin/donors/{$id}/decline", ['reason' => 'Could not verify the organisation.']);

    $profile = DB::table('donor_profiles')->where('id', $id)->first();
    expect($profile->approval_status)->toBe('declined')
        ->and($profile->review_reason)->toBe('Could not verify the organisation.');
    $this->assertDatabaseHas('audit_events', ['action' => 'donor.declined', 'subject_id' => $id]);
});

it('cannot decide the same registration twice', function () {
    [, $id] = makePendingDonor();
    $admin = adminUser();

    $this->actingAs($admin)->post("/admin/donors/{$id}/approve");
    $this->actingAs($admin)->post("/admin/donors/{$id}/decline", ['reason' => 'Changed my mind later.'])
        ->assertSessionHas('error');

    expect(DB::table('donor_profiles')->where('id', $id)->value('approval_status'))->toBe('approved')
        ->and(DB::table('audit_events')->where('subject_id', $id)->count())->toBe(1)
        ->and(DB::table('notifications')->count())->toBe(1);
});

it('reports a registration that does not exist', function () {
    $this->actingAs(adminUser())->post('/admin/donors/99999/approve')->assertSessionHas('error');
});
