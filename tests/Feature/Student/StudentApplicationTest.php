<?php

use App\Models\User;
use Database\Seeders\ReferenceDataSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(ReferenceDataSeeder::class);
    $this->withoutVite();
    Storage::fake('local');
});

function newStudent(bool $complete = true, string $matric = 'GSU/2022/0001'): User
{
    $user = User::factory()->create()->assignRole('student');
    $row = ['user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()];

    if ($complete) {
        $institutionId = DB::table('institutions')->where('code', 'GSU')->value('id');
        $row += [
            'institution_id' => $institutionId,
            'programme_id' => DB::table('programmes')->where('institution_id', $institutionId)->value('id'),
            'matric_number' => $matric,
            'level' => 200,
            'origin_lga_id' => DB::table('local_governments')->value('id'),
            'phone' => '08012345678',
        ];
    }

    DB::table('student_profiles')->insert($row);

    return $user;
}

/** @return array<string, mixed> */
function applicationData(array $overrides = []): array
{
    return array_merge([
        'monthly_income' => 50000,
        'tuition' => 150000,
        'prior_arrears' => 20000,
        'external_aid' => 0,
        'dependants' => 3,
        'is_orphan' => 0,
        'single_parent' => 1,
        'disability' => 0,
        'result_availability' => 'available',
    ], $overrides);
}

function startDraft(User $student): void
{
    test()->actingAs($student)->post('/student/application/start');
}

function uploadDocument(User $student, string $purpose = 'academic_result')
{
    return test()->actingAs($student)->post('/student/application/documents', [
        'file' => UploadedFile::fake()->create('result.pdf', 200, 'application/pdf'),
        'purpose' => $purpose,
    ]);
}

it('saves a student profile', function () {
    $student = newStudent(false);
    $institutionId = DB::table('institutions')->where('code', 'GSU')->value('id');

    $this->actingAs($student)->put('/student/profile', [
        'institution_id' => $institutionId,
        'programme_id' => DB::table('programmes')->where('institution_id', $institutionId)->value('id'),
        'matric_number' => ' GSU/2020/001 ',
        'level' => 300,
        'origin_lga_id' => DB::table('local_governments')->value('id'),
        'phone' => '0801 234 5678',
    ])->assertSessionHas('status');

    $this->assertDatabaseHas('student_profiles', [
        'user_id' => $student->id,
        'matric_number' => 'GSU/2020/001',
        'level' => 300,
        'enrolment_status' => 'pending',
    ]);
});

it('rejects a programme that does not belong to the chosen institution', function () {
    $student = newStudent(false);
    $institutionId = DB::table('institutions')->where('code', 'GSU')->value('id');
    $foreignProgramme = DB::table('programmes')->where('institution_id', '!=', $institutionId)->value('id');

    $this->actingAs($student)->put('/student/profile', [
        'institution_id' => $institutionId,
        'programme_id' => $foreignProgramme,
        'matric_number' => 'GSU/2020/002',
        'level' => 300,
        'origin_lga_id' => DB::table('local_governments')->value('id'),
        'phone' => '08012345678',
    ])->assertSessionHasErrors('programme_id');
});

it('does not allow two students to share a matric number at one institution', function () {
    newStudent(true, 'GSU/DUP/1');
    $second = newStudent(false);
    $institutionId = DB::table('institutions')->where('code', 'GSU')->value('id');

    $this->actingAs($second)->put('/student/profile', [
        'institution_id' => $institutionId,
        'programme_id' => DB::table('programmes')->where('institution_id', $institutionId)->value('id'),
        'matric_number' => 'GSU/DUP/1',
        'level' => 200,
        'origin_lga_id' => DB::table('local_governments')->value('id'),
        'phone' => '08012345678',
    ])->assertSessionHasErrors('matric_number');
});

it('keeps non-students out of the student area', function (string $role) {
    $user = User::factory()->create()->assignRole($role);

    $this->actingAs($user)->get('/student/application')->assertForbidden();
    $this->actingAs($user)->get('/student/profile')->assertForbidden();
})->with(['admin', 'donor', 'super_admin']);

it('asks for a complete profile before an application can start', function () {
    $student = newStudent(false);

    $this->actingAs($student)->get('/student/application')->assertInertia(fn (Assert $page) => $page
        ->where('state', 'profile_incomplete')
        ->where('missing_profile.0', 'Institution'));

    $this->actingAs($student)->post('/student/application/start')->assertRedirect('/student/profile');
    expect(DB::table('applications')->count())->toBe(0);
});

it('starts one draft with a 26-character reference and never a second', function () {
    $student = newStudent();

    startDraft($student);
    startDraft($student);

    expect(DB::table('applications')->count())->toBe(1)
        ->and(strlen((string) DB::table('applications')->value('public_reference')))->toBe(26)
        ->and(DB::table('application_drafts')->count())->toBe(1);
});

it('saves draft answers', function () {
    $student = newStudent();
    startDraft($student);

    $this->actingAs($student)->put('/student/application/draft', ['monthly_income' => 40000, 'dependants' => 4])
        ->assertSessionHas('status');

    $this->actingAs($student)->get('/student/application')->assertInertia(fn (Assert $page) => $page
        ->where('state', 'draft')
        ->where('draft.monthly_income', 40000)
        ->where('draft.dependants', 4));
});

it('stores evidence on the private disk with its hash and a pending scan', function () {
    $student = newStudent();
    startDraft($student);

    uploadDocument($student)->assertSessionHas('status');

    $document = DB::table('documents')->first();
    expect($document->owner_id)->toBe($student->id)
        ->and($document->storage_disk)->toBe('local')
        ->and($document->scan_status)->toBe('pending')
        ->and(strlen($document->sha256))->toBe(64);
    Storage::disk('local')->assertExists($document->object_key);
});

it('rejects files that are not pdf or images, or are too large', function () {
    $student = newStudent();
    startDraft($student);

    $this->actingAs($student)->post('/student/application/documents', [
        'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload'),
        'purpose' => 'other',
    ])->assertSessionHasErrors('file');

    $this->actingAs($student)->post('/student/application/documents', [
        'file' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        'purpose' => 'other',
    ])->assertSessionHasErrors('file');

    expect(DB::table('documents')->count())->toBe(0);
});

it('submits an application as an immutable snapshot in kobo', function () {
    $student = newStudent();
    startDraft($student);
    uploadDocument($student);

    $this->actingAs($student)->post('/student/application/submit', applicationData())
        ->assertRedirect('/student/application')
        ->assertSessionHas('status');

    $application = DB::table('applications')->first();
    expect($application->status)->toBe('submitted')->and($application->submitted_at)->not->toBeNull();

    $version = DB::table('application_versions')->first();
    expect($version->version_no)->toBe(1)
        ->and($version->monthly_income)->toBe(5000000)
        ->and($version->tuition)->toBe(15000000)
        ->and($version->prior_arrears)->toBe(2000000)
        ->and($version->dependants)->toBe(3)
        ->and((bool) $version->external_aid)->toBeFalse()
        ->and($version->external_aid_amount)->toBeNull()
        ->and((bool) $version->single_parent)->toBeTrue();

    $this->assertDatabaseHas('application_documents', [
        'application_version_id' => $version->id,
        'purpose' => 'academic_result',
        'linked_by' => $student->id,
    ]);
    $this->assertDatabaseHas('audit_events', ['action' => 'application.submitted', 'subject_id' => $application->id]);
    expect(DB::table('application_drafts')->count())->toBe(0);

    $this->actingAs($student)->get('/student/application')->assertInertia(fn (Assert $page) => $page
        ->where('state', 'submitted')
        ->where('submitted.monthly_income', 50000));
});

it('requires the right evidence for the result choice', function () {
    $student = newStudent();
    startDraft($student);
    uploadDocument($student, 'other');

    $this->actingAs($student)->post('/student/application/submit', applicationData())->assertSessionHasErrors('documents');

    $this->actingAs($student)->post('/student/application/submit', applicationData(['result_availability' => 'not_yet_available']))
        ->assertSessionHasErrors('documents');

    uploadDocument($student, 'admission_letter');
    $this->actingAs($student)->post('/student/application/submit', applicationData(['result_availability' => 'not_yet_available']))
        ->assertSessionHasNoErrors();

    expect(DB::table('application_versions')->count())->toBe(1);
});

it('refuses to submit incomplete answers', function () {
    $student = newStudent();
    startDraft($student);
    uploadDocument($student);

    $this->actingAs($student)->post('/student/application/submit', [])
        ->assertSessionHasErrors(['monthly_income', 'tuition', 'prior_arrears', 'external_aid', 'dependants', 'is_orphan', 'result_availability']);

    expect(DB::table('application_versions')->count())->toBe(0)
        ->and(DB::table('applications')->value('status'))->toBe('draft');
});

it('requires an amount when other aid is declared', function () {
    $student = newStudent();
    startDraft($student);
    uploadDocument($student);

    $this->actingAs($student)->post('/student/application/submit', applicationData(['external_aid' => 1]))
        ->assertSessionHasErrors('external_aid_amount');

    $this->actingAs($student)->post('/student/application/submit', applicationData(['external_aid' => 1, 'external_aid_amount' => 10000]))
        ->assertSessionHasNoErrors();

    expect((int) DB::table('application_versions')->value('external_aid_amount'))->toBe(1000000);
});

it('locks a submitted application against edits, uploads and a second submit', function () {
    $student = newStudent();
    startDraft($student);
    uploadDocument($student);
    $this->actingAs($student)->post('/student/application/submit', applicationData());

    $this->actingAs($student)->put('/student/application/draft', ['monthly_income' => 1])->assertSessionHas('error');
    uploadDocument($student)->assertSessionHas('error');
    $this->actingAs($student)->post('/student/application/submit', applicationData())->assertSessionHas('error');

    expect(DB::table('application_versions')->count())->toBe(1)
        ->and((int) DB::table('application_versions')->value('monthly_income'))->toBe(5000000);
});

it('lets a student remove only their own draft document', function () {
    $owner = newStudent(true, 'GSU/OWN/1');
    $other = newStudent(true, 'GSU/OWN/2');
    startDraft($owner);
    startDraft($other);
    uploadDocument($owner);
    $document = DB::table('documents')->first();

    $this->actingAs($other)->delete("/student/application/documents/{$document->id}")->assertNotFound();
    expect(DB::table('documents')->count())->toBe(1);

    $this->actingAs($owner)->delete("/student/application/documents/{$document->id}")->assertSessionHas('status');
    expect(DB::table('documents')->count())->toBe(0);
    Storage::disk('local')->assertMissing($document->object_key);
});
