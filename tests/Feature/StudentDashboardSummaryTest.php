<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->withoutVite();
});

it('does not count a draft as an application and shows the real status', function () {
    $student = User::factory()->create()->assignRole('student');
    $profileId = DB::table('student_profiles')->insertGetId(['user_id' => $student->id, 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($student)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('summary.applications', 0)
        ->where('summary.application_status', null));

    $applicationId = DB::table('applications')->insertGetId([
        'student_id' => $profileId,
        'status' => 'draft',
        'public_reference' => (string) Str::ulid(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($student)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('summary.applications', 0)
        ->where('summary.application_status', 'draft'));

    DB::table('applications')->where('id', $applicationId)->update(['status' => 'submitted']);

    $this->actingAs($student)->get('/dashboard')->assertInertia(fn (Assert $page) => $page
        ->where('summary.applications', 1)
        ->where('summary.application_status', 'submitted'));
});
