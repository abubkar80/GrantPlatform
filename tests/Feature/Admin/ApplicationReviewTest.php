<?php

use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ReferenceDataSeeder::class);
    $this->withoutVite();
    Storage::fake('local');
});

/** @return array<string, int|User> */
function reviewFixture(array $version = []): array
{
    $student = User::factory()->create(['name' => 'Amina Bello'])->assignRole('student');
    $institutionId = DB::table('institutions')->where('code', 'GSU')->value('id');
    $programmeId = DB::table('programmes')->where('institution_id', $institutionId)->value('id');
    $lgaId = DB::table('local_governments')->value('id');

    $profileId = DB::table('student_profiles')->insertGetId([
        'user_id' => $student->id,
        'institution_id' => $institutionId,
        'programme_id' => $programmeId,
        'matric_number' => 'GSU/'.Str::random(6),
        'level' => 200,
        'origin_lga_id' => $lgaId,
        'phone' => '08012345678',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $applicationId = DB::table('applications')->insertGetId([
        'student_id' => $profileId,
        'status' => 'submitted',
        'public_reference' => (string) Str::ulid(),
        'submitted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $versionId = DB::table('application_versions')->insertGetId(array_merge([
        'application_id' => $applicationId,
        'version_no' => 1,
        'programme_id' => $programmeId,
        'matric_number' => 'GSU/X',
        'level' => 200,
        'origin_lga_id' => $lgaId,
        'enrolment_status' => 'pending',
        'monthly_income' => 5_000_000,
        'tuition' => 15_000_000,
        'prior_arrears' => 2_000_000,
        'education_period_id' => DB::table('academic_periods')->value('id'),
        'external_aid' => false,
        'external_aid_amount' => null,
        'dependants' => 3,
        'is_orphan' => false,
        'single_parent' => true,
        'disability' => false,
        'result_availability' => 'available',
        'declared_at' => now(),
        'created_at' => now(),
    ], $version));

    // Each fixture gets its own file: (disk, object_key) must be unique in the documents table.
    $objectKey = 'evidence/test/'.Str::lower(Str::random(12)).'.pdf';
    Storage::disk('local')->put($objectKey, 'pdf-bytes');
    $documentId = DB::table('documents')->insertGetId([
        'owner_id' => $student->id,
        'uploaded_by' => $student->id,
        'storage_disk' => 'local',
        'object_key' => $objectKey,
        'original_name' => 'result.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 9,
        'sha256' => str_repeat('a', 64),
        'scan_status' => 'pending',
        'created_at' => now(),
    ]);
    DB::table('application_documents')->insert([
        'application_version_id' => $versionId,
        'document_id' => $documentId,
        'linked_by' => $student->id,
        'purpose' => 'academic_result',
        'created_at' => now(),
    ]);

    return compact('student', 'profileId', 'applicationId', 'versionId', 'documentId');
}

function reviewer(): User
{
    return User::factory()->create()->assignRole('admin');
}

/** @return array<string, mixed> */
function fullAssessment(array $overrides = []): array
{
    return array_merge([
        'verified' => ['income' => 1, 'costs' => 1, 'support' => 1, 'dependants' => 1, 'circumstances' => 1],
        'risk_level' => 'high',
        'risk_reason' => 'The family cannot cover the fees.',
    ], $overrides);
}

function startedReview(array $version = []): array
{
    $fixture = reviewFixture($version);
    $admin = reviewer();
    test()->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/start");

    return [$fixture, $admin];
}

it('keeps everyone except Admins out of the review area', function (string $role) {
    $fixture = reviewFixture();
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)->get('/admin/applications')->assertForbidden();
    $this->actingAs($user)->get("/admin/applications/{$fixture['applicationId']}")->assertForbidden();
    $this->actingAs($user)->post("/admin/applications/{$fixture['applicationId']}/start")->assertForbidden();
    $this->actingAs($user)->get("/admin/applications/{$fixture['applicationId']}/documents/{$fixture['documentId']}")->assertForbidden();
})->with(['student', 'donor', 'super_admin']);

it('lists submitted applications and never drafts', function () {
    reviewFixture();
    $draftStudent = DB::table('student_profiles')->insertGetId([
        'user_id' => User::factory()->create()->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('applications')->insert([
        'student_id' => $draftStudent, 'status' => 'draft', 'public_reference' => (string) Str::ulid(),
        'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->actingAs(reviewer())->get('/admin/applications')->assertInertia(fn (Assert $page) => $page
        ->component('admin/applications')
        ->where('filter', 'submitted')
        ->has('applications', 1)
        ->where('applications.0.name', 'Amina Bello')
        ->where('counts.submitted', 1));
});

it('shows the declared answers, evidence and student details', function () {
    $fixture = reviewFixture();

    $this->actingAs(reviewer())->get("/admin/applications/{$fixture['applicationId']}")->assertInertia(fn (Assert $page) => $page
        ->component('admin/application-review')
        ->where('student.name', 'Amina Bello')
        ->where('answers.monthly_income', 50000)
        ->where('answers.tuition', 150000)
        ->has('documents', 1)
        ->where('assessment', null));
});

it('starts a review once, assigns the case and audits it', function () {
    $fixture = reviewFixture();
    $admin = reviewer();

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/start")->assertSessionHas('status');

    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('under_review');
    $this->assertDatabaseHas('case_assignments', ['application_id' => $fixture['applicationId'], 'admin_id' => $admin->id, 'ended_at' => null]);
    $this->assertDatabaseHas('audit_events', ['action' => 'application.review_started', 'subject_id' => $fixture['applicationId']]);

    $this->actingAs(reviewer())->post("/admin/applications/{$fixture['applicationId']}/start")->assertSessionHas('error');
    expect(DB::table('case_assignments')->count())->toBe(1);
});

it('will not assess before the review has started', function () {
    $fixture = reviewFixture();

    $this->actingAs(reviewer())->post("/admin/applications/{$fixture['applicationId']}/assessment", fullAssessment())
        ->assertSessionHas('error');

    expect(DB::table('need_assessments')->count())->toBe(0);
});

it('scores only verified evidence using the workbook model', function () {
    [$fixture, $admin] = startedReview();

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/assessment", fullAssessment())
        ->assertSessionHas('status');

    $assessment = DB::table('need_assessments')->first();
    expect($assessment->status)->toBe('complete')
        ->and($assessment->income_points)->toBe(30)
        ->and($assessment->costs_points)->toBe(10)
        ->and($assessment->support_points)->toBe(15)
        ->and($assessment->dependant_points)->toBe(5)
        ->and($assessment->circumstance_points)->toBe(10)
        ->and($assessment->risk_points)->toBe(10)
        ->and($assessment->total_score)->toBe(80)
        ->and($assessment->category)->toBe('high')
        ->and($assessment->verified_unmet_cost)->toBe(17_000_000);

    $this->assertDatabaseHas('audit_events', ['action' => 'application.assessed', 'subject_id' => $fixture['applicationId']]);
});

it('keeps the assessment incomplete when an indicator is not verified, never zero', function () {
    [$fixture, $admin] = startedReview();

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/assessment", fullAssessment([
        'verified' => ['income' => 0, 'costs' => 1, 'support' => 1, 'dependants' => 1, 'circumstances' => 1],
    ]));

    $assessment = DB::table('need_assessments')->first();
    expect($assessment->status)->toBe('pending')
        ->and($assessment->total_score)->toBeNull()
        ->and($assessment->income_points)->toBeNull();

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/decision", ['outcome' => 'approved'])
        ->assertSessionHas('error');
    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('under_review');
});

it('approves with a complete assessment, confirms enrolment and notifies the student', function () {
    [$fixture, $admin] = startedReview();
    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/assessment", fullAssessment());

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/decision", ['outcome' => 'approved'])
        ->assertSessionHas('status');

    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('approved')
        ->and(DB::table('student_profiles')->where('id', $fixture['profileId'])->value('enrolment_status'))->toBe('active');
    $this->assertDatabaseHas('application_reviews', [
        'application_version_id' => $fixture['versionId'],
        'reviewer_id' => $admin->id,
        'outcome' => 'approved',
        'reason' => 'Evidence verified and enrolment confirmed.',
    ]);
    $this->assertDatabaseHas('enrolment_events', ['student_id' => $fixture['profileId'], 'from_status' => 'pending', 'to_status' => 'active']);
    $this->assertDatabaseMissing('case_assignments', ['application_id' => $fixture['applicationId'], 'ended_at' => null]);
    $this->assertDatabaseHas('audit_events', ['action' => 'application.approved', 'subject_id' => $fixture['applicationId']]);
    $this->assertDatabaseHas('notifications', ['user_id' => $fixture['student']->id, 'kind' => 'application.approved']);
});

it('declines only with a reason and cannot decide twice', function () {
    [$fixture, $admin] = startedReview();

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/decision", ['outcome' => 'declined'])
        ->assertSessionHasErrors('reason');
    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('under_review');

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/decision", ['outcome' => 'declined', 'reason' => 'Evidence did not match the declaration.']);
    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('declined');

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/decision", ['outcome' => 'approved'])
        ->assertSessionHas('error');
    expect(DB::table('application_reviews')->count())->toBe(1);
});

it('cannot decide an application that is not under review', function () {
    $fixture = reviewFixture();

    $this->actingAs(reviewer())->post("/admin/applications/{$fixture['applicationId']}/decision", ['outcome' => 'declined', 'reason' => 'Some reason here.'])
        ->assertSessionHas('error');

    expect(DB::table('application_reviews')->count())->toBe(0);
});

it('lets a student update and resubmit after more information is requested', function () {
    [$fixture, $admin] = startedReview();

    $this->actingAs($admin)->post("/admin/applications/{$fixture['applicationId']}/decision", [
        'outcome' => 'needs_information', 'reason' => 'Please correct your tuition amount.',
    ]);
    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('needs_information');

    $student = $fixture['student'];
    $this->actingAs($student)->get('/student/application')->assertInertia(fn (Assert $page) => $page
        ->where('state', 'submitted')
        ->where('review.reason', 'Please correct your tuition amount.'));

    $this->actingAs($student)->post('/student/application/reopen')->assertSessionHas('status');
    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('draft');

    $this->actingAs($student)->get('/student/application')->assertInertia(fn (Assert $page) => $page
        ->where('state', 'draft')
        ->where('draft.tuition', 150000)
        ->has('documents', 1));

    $this->actingAs($student)->post('/student/application/submit', [
        'monthly_income' => 50000, 'tuition' => 120000, 'prior_arrears' => 20000, 'external_aid' => 0,
        'dependants' => 3, 'is_orphan' => 0, 'single_parent' => 1, 'disability' => 0, 'result_availability' => 'available',
    ])->assertSessionHasNoErrors();

    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('submitted')
        ->and(DB::table('application_versions')->where('application_id', $fixture['applicationId'])->count())->toBe(2)
        ->and((int) DB::table('application_versions')->where('application_id', $fixture['applicationId'])->where('version_no', 2)->value('tuition'))->toBe(12_000_000);
});

it('does not let a student reopen unless more information was requested', function () {
    $fixture = reviewFixture();

    $this->actingAs($fixture['student'])->post('/student/application/reopen')->assertSessionHas('error');
    expect(DB::table('applications')->where('id', $fixture['applicationId'])->value('status'))->toBe('submitted');
});

it('serves linked evidence to an Admin, audits it, and refuses anything else', function () {
    $fixture = reviewFixture();
    $other = reviewFixture();
    $admin = reviewer();

    $response = $this->actingAs($admin)->get("/admin/applications/{$fixture['applicationId']}/documents/{$fixture['documentId']}");
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('result.pdf');
    $this->assertDatabaseHas('audit_events', ['action' => 'document.viewed', 'subject_id' => $fixture['documentId']]);

    // A document that belongs to a different application cannot be fetched through this one.
    $this->actingAs($admin)->get("/admin/applications/{$fixture['applicationId']}/documents/{$other['documentId']}")->assertNotFound();
});
