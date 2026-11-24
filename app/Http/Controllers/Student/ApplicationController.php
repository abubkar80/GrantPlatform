<?php

namespace App\Http\Controllers\Student;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Student application: draft -> submit. A submit freezes an immutable application_versions snapshot
 * (SRS: unknown stays NULL, money in kobo). Evidence goes to private storage and is linked to that snapshot.
 */
class ApplicationController
{
    private const PURPOSES = ['academic_result', 'admission_letter', 'income', 'tuition', 'other'];

    private const PROFILE_FIELDS = [
        'institution_id' => 'Institution',
        'programme_id' => 'Programme',
        'matric_number' => 'Matric number',
        'level' => 'Level',
        'origin_lga_id' => 'Local government of origin',
    ];

    public function show(Request $request): Response
    {
        $profile = $this->profile($request->user());
        $missing = $this->missingProfileFields($profile);
        $application = $profile
            ? DB::table('applications')->where('student_id', $profile->id)->latest('id')->first()
            : null;

        $props = [
            'state' => 'none',
            'missing_profile' => $missing,
            'application' => null,
            'draft' => null,
            'documents' => [],
            'submitted' => null,
            'purposes' => self::PURPOSES,
        ];

        if ($missing !== []) {
            $props['state'] = 'profile_incomplete';
        } elseif ($application !== null) {
            $props['application'] = [
                'reference' => $application->public_reference,
                'status' => $application->status,
                'submitted_at' => $application->submitted_at,
            ];

            if ($application->status === 'draft') {
                $draft = $this->loadDraft($application->id);
                $props['state'] = 'draft';
                $props['draft'] = $this->draftFields($draft);
                $props['documents'] = $draft['documents'] ?? [];
            } else {
                $props['state'] = 'submitted';
                $props = array_merge($props, $this->submittedView($application->id));
            }
        }

        return Inertia::render('student/application', $props);
    }

    public function start(Request $request): RedirectResponse
    {
        $profile = $this->profile($request->user());

        if ($this->missingProfileFields($profile) !== []) {
            return redirect('/student/profile')->with('error', 'Complete your profile before you start an application.');
        }

        $this->draftApplication($profile->id);

        return redirect('/student/application');
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $profile = $this->profile($request->user());
        $application = $this->openDraft($profile);

        if ($application === null) {
            return back()->with('error', 'There is no draft to save. Submitted applications cannot be edited.');
        }

        $data = $request->validate([
            'monthly_income' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'tuition' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'prior_arrears' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'external_aid' => ['nullable', 'boolean'],
            'external_aid_amount' => ['nullable', 'integer', 'min:0', 'max:1000000000'],
            'dependants' => ['nullable', 'integer', 'min:0', 'max:30'],
            'is_orphan' => ['nullable', 'boolean'],
            'single_parent' => ['nullable', 'boolean'],
            'disability' => ['nullable', 'boolean'],
            'result_availability' => ['nullable', Rule::in(['available', 'not_yet_available'])],
        ]);

        $draft = array_merge($this->loadDraft($application->id), $data);

        DB::table('application_drafts')->where('application_id', $application->id)->update([
            'payload' => json_encode($draft, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Draft saved.');
    }

    public function upload(Request $request): RedirectResponse
    {
        $user = $request->user();
        $application = $this->openDraft($this->profile($user));

        if ($application === null) {
            return back()->with('error', 'Start an application before uploading documents.');
        }

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'purpose' => ['required', Rule::in(self::PURPOSES)],
        ]);

        $file = $request->file('file');
        $key = 'evidence/'.$user->id.'/'.Str::lower((string) Str::ulid()).'.'.$file->extension();

        // Private disk: never reachable by a public URL; downloads will go through an authorised route.
        $stored = Storage::disk('local')->putFileAs(dirname($key), $file, basename($key));
        if ($stored === false) {
            return back()->with('error', 'The file could not be stored. Try again.');
        }

        $now = now();
        $documentId = DB::table('documents')->insertGetId([
            'owner_id' => $user->id,
            'uploaded_by' => $user->id,
            'storage_disk' => 'local',
            'object_key' => $key,
            'original_name' => Str::limit(preg_replace('/[\x00-\x1F\x7F]/', '', $file->getClientOriginalName()) ?? 'document', 200, ''),
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => $file->getSize(),
            'sha256' => (string) hash_file('sha256', $file->getRealPath()),
            'scan_status' => 'pending', // no malware scanner in this build; stays pending and is shown as such
            'created_at' => $now,
        ]);

        $draft = $this->loadDraft($application->id);
        $draft['documents'][] = [
            'id' => $documentId,
            'purpose' => $request->string('purpose')->toString(),
            'name' => DB::table('documents')->where('id', $documentId)->value('original_name'),
            'size' => $file->getSize(),
        ];

        DB::table('application_drafts')->where('application_id', $application->id)->update([
            'payload' => json_encode($draft, JSON_THROW_ON_ERROR),
            'updated_at' => $now,
        ]);

        return back()->with('status', 'Document uploaded.');
    }

    public function removeDocument(Request $request, int $document): RedirectResponse
    {
        $application = $this->openDraft($this->profile($request->user()));
        abort_if($application === null, 404);

        $draft = $this->loadDraft($application->id);
        $documents = $this->draftDocuments($draft);

        // Only documents attached to this student's own draft can be removed.
        abort_unless($documents->contains('id', $document), 404);

        $row = DB::table('documents')->where('id', $document)->where('owner_id', $request->user()->id)->first();
        abort_if($row === null, 404);

        $draft['documents'] = $documents->reject(fn ($d) => $d['id'] === $document)->values()->all();
        DB::table('application_drafts')->where('application_id', $application->id)->update([
            'payload' => json_encode($draft, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ]);
        DB::table('documents')->where('id', $document)->delete();
        Storage::disk($row->storage_disk)->delete($row->object_key);

        return back()->with('status', 'Document removed.');
    }

    public function submit(Request $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $this->profile($user);
        $application = $this->openDraft($profile);

        if ($application === null || $profile === null) {
            return back()->with('error', 'There is no open draft to submit.');
        }

        $data = $request->validate([
            'monthly_income' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'tuition' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'prior_arrears' => ['required', 'integer', 'min:0', 'max:1000000000'],
            'external_aid' => ['required', 'boolean'],
            'external_aid_amount' => ['nullable', 'required_if:external_aid,1', 'integer', 'min:0', 'max:1000000000'],
            'dependants' => ['required', 'integer', 'min:0', 'max:30'],
            'is_orphan' => ['required', 'boolean'],
            'single_parent' => ['required', 'boolean'],
            'disability' => ['required', 'boolean'],
            'result_availability' => ['required', Rule::in(['available', 'not_yet_available'])],
        ]);

        DB::transaction(function () use ($application, $profile, $data, $user) {
            // Lock the application so a double click cannot create two snapshots.
            $locked = DB::table('applications')->where('id', $application->id)->where('status', 'draft')->lockForUpdate()->first();
            if ($locked === null) {
                throw ValidationException::withMessages(['application' => 'This application was already submitted.']);
            }

            $documents = $this->draftDocuments($this->loadDraft($application->id));
            $required = $data['result_availability'] === 'available' ? 'academic_result' : 'admission_letter';
            if (! $documents->contains('purpose', $required)) {
                throw ValidationException::withMessages([
                    'documents' => $required === 'academic_result'
                        ? 'Upload your latest semester result.'
                        : 'Upload your admission letter (new entrants without results).',
                ]);
            }

            $periodId = DB::table('academic_periods')->where('institution_id', $profile->institution_id)->orderByDesc('id')->value('id');
            if ($periodId === null) {
                throw ValidationException::withMessages(['application' => 'No academic period is set up for your institution yet.']);
            }

            $now = now();
            $aid = (bool) $data['external_aid'];
            $versionNo = (int) DB::table('application_versions')->where('application_id', $application->id)->max('version_no') + 1;

            $versionId = DB::table('application_versions')->insertGetId([
                'application_id' => $application->id,
                'version_no' => $versionNo,
                'programme_id' => $profile->programme_id,
                'matric_number' => $profile->matric_number,
                'level' => $profile->level,
                'origin_lga_id' => $profile->origin_lga_id,
                'enrolment_status' => $profile->enrolment_status,
                'monthly_income' => (int) $data['monthly_income'] * 100,
                'tuition' => (int) $data['tuition'] * 100,
                'prior_arrears' => (int) $data['prior_arrears'] * 100,
                'education_period_id' => $periodId,
                'external_aid' => $aid,
                'external_aid_amount' => $aid ? (int) $data['external_aid_amount'] * 100 : null,
                'dependants' => (int) $data['dependants'],
                'is_orphan' => (bool) $data['is_orphan'],
                'single_parent' => (bool) $data['single_parent'],
                'disability' => (bool) $data['disability'],
                'result_availability' => $data['result_availability'],
                'declared_at' => $now,
                'created_at' => $now,
            ]);

            foreach ($documents as $document) {
                DB::table('application_documents')->insert([
                    'application_version_id' => $versionId,
                    'document_id' => $document['id'],
                    'linked_by' => $user->id,
                    'purpose' => $document['purpose'],
                    'created_at' => $now,
                ]);
            }

            DB::table('applications')->where('id', $application->id)->update([
                'status' => 'submitted',
                'submitted_at' => $now,
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => $now,
            ]);
            DB::table('application_drafts')->where('application_id', $application->id)->delete();

            Audit::record(
                action: 'application.submitted',
                subjectType: 'application',
                subjectId: $application->id,
                actorId: $user->id,
                changes: ['version_no' => $versionNo, 'documents' => $documents->count()],
            );
        });

        return redirect('/student/application')->with('status', 'Application submitted. You will be notified when it is reviewed.');
    }

    private function profile(User $user): ?\stdClass
    {
        return DB::table('student_profiles')->where('user_id', $user->id)->first();
    }

    /** @return list<string> */
    private function missingProfileFields(?\stdClass $profile): array
    {
        if ($profile === null) {
            return array_values(self::PROFILE_FIELDS);
        }

        $row = (array) $profile;

        return array_values(array_filter(
            self::PROFILE_FIELDS,
            fn (string $label, string $field) => ($row[$field] ?? null) === null || $row[$field] === '',
            ARRAY_FILTER_USE_BOTH,
        ));
    }

    /** The student's single open draft application, if any (never a submitted one). */
    private function openDraft(?\stdClass $profile): ?\stdClass
    {
        if ($profile === null) {
            return null;
        }

        return DB::table('applications')->where('student_id', $profile->id)->where('status', 'draft')->first();
    }

    /** Create (or reuse) the draft application and its empty draft payload. */
    private function draftApplication(int $studentId): int
    {
        $existing = DB::table('applications')->where('student_id', $studentId)->whereIn('status', ['draft', 'submitted', 'under_review', 'needs_information'])->first();
        if ($existing !== null) {
            return $existing->id;
        }

        $now = now();
        $id = DB::table('applications')->insertGetId([
            'student_id' => $studentId,
            'status' => 'draft',
            'public_reference' => (string) Str::ulid(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('application_drafts')->insert([
            'application_id' => $id,
            'payload' => json_encode(['documents' => []], JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    /** @return array<string, mixed> */
    private function loadDraft(int $applicationId): array
    {
        $payload = DB::table('application_drafts')->where('application_id', $applicationId)->value('payload');

        return is_string($payload) ? (array) json_decode($payload, true, 512, JSON_THROW_ON_ERROR) : ['documents' => []];
    }

    /** @return array<string, mixed> */
    private function submittedView(int $applicationId): array
    {
        $version = DB::table('application_versions')->where('application_id', $applicationId)->orderByDesc('version_no')->first();
        if ($version === null) {
            return [];
        }

        return [
            'submitted' => [
                'version_no' => $version->version_no,
                'monthly_income' => intdiv((int) $version->monthly_income, 100),
                'tuition' => intdiv((int) $version->tuition, 100),
                'prior_arrears' => intdiv((int) $version->prior_arrears, 100),
                'external_aid' => (bool) $version->external_aid,
                'external_aid_amount' => $version->external_aid_amount === null ? null : intdiv((int) $version->external_aid_amount, 100),
                'dependants' => $version->dependants,
                'is_orphan' => (bool) $version->is_orphan,
                'single_parent' => (bool) $version->single_parent,
                'disability' => (bool) $version->disability,
                'result_availability' => $version->result_availability,
                'declared_at' => $version->declared_at,
            ],
            'documents' => DB::table('application_documents as ad')
                ->join('documents as d', 'd.id', '=', 'ad.document_id')
                ->where('ad.application_version_id', $version->id)
                ->get(['d.id', 'ad.purpose', 'd.original_name as name', 'd.size_bytes as size']),
        ];
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return Collection<int, array{id: int, purpose: string, name: string, size: int}>
     */
    private function draftDocuments(array $draft): Collection
    {
        /** @var list<array{id: int, purpose: string, name: string, size: int}> $documents */
        $documents = $draft['documents'] ?? [];

        return collect($documents);
    }

    /**
     * @param  array<string, mixed>  $draft
     * @return array<string, mixed>
     */
    private function draftFields(array $draft): array
    {
        $fields = [
            'monthly_income', 'tuition', 'prior_arrears', 'external_aid', 'external_aid_amount',
            'dependants', 'is_orphan', 'single_parent', 'disability', 'result_availability',
        ];

        return collect($fields)->mapWithKeys(fn (string $f) => [$f => $draft[$f] ?? null])->all();
    }
}
