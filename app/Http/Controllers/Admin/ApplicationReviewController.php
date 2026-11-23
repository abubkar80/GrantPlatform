<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Scoring\NeedInputs;
use App\Domain\Scoring\NeedScorer;
use App\Domain\Scoring\ScoringPolicy;
use App\Support\Audit;
use App\Support\ScoringPolicies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use stdClass;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin application review: start a review, verify evidence and score need, then approve,
 * decline or ask for more information. Every step is audited; decisions are one-way and atomic.
 */
class ApplicationReviewController
{
    private const FILTERS = ['submitted', 'under_review', 'needs_information', 'approved', 'declined', 'all'];

    private const INDICATORS = [
        'income' => 'Household income',
        'costs' => 'Tuition and earlier arrears',
        'support' => 'Other financial aid',
        'dependants' => 'Dependants',
        'circumstances' => 'Orphan / single parent / disability',
    ];

    public function index(Request $request): Response
    {
        $filter = in_array($request->query('status'), self::FILTERS, true) ? $request->query('status') : 'submitted';

        $applications = DB::table('applications as a')
            ->join('student_profiles as s', 's.id', '=', 'a.student_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('institutions as i', 'i.id', '=', 's.institution_id')
            ->leftJoin('programmes as p', 'p.id', '=', 's.programme_id')
            ->where('a.status', '!=', 'draft')
            ->when($filter !== 'all', fn ($query) => $query->where('a.status', $filter))
            ->orderByDesc('a.submitted_at')
            ->limit(200)
            ->get(['a.id', 'a.public_reference', 'a.status', 'a.submitted_at', 'u.name', 'i.name as institution', 'p.name as programme']);

        $counts = DB::table('applications')->where('status', '!=', 'draft')
            ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return Inertia::render('admin/applications', [
            'applications' => $applications,
            'filter' => $filter,
            'counts' => collect(self::FILTERS)->reject(fn ($f) => $f === 'all')
                ->mapWithKeys(fn ($f) => [$f => (int) ($counts[$f] ?? 0)])->all(),
        ]);
    }

    public function show(int $application): Response
    {
        $row = DB::table('applications as a')
            ->join('student_profiles as s', 's.id', '=', 'a.student_id')
            ->join('users as u', 'u.id', '=', 's.user_id')
            ->leftJoin('institutions as i', 'i.id', '=', 's.institution_id')
            ->leftJoin('programmes as p', 'p.id', '=', 's.programme_id')
            ->leftJoin('local_governments as l', 'l.id', '=', 's.origin_lga_id')
            ->where('a.id', $application)
            ->first([
                'a.id', 'a.public_reference', 'a.status', 'a.submitted_at',
                'u.name', 'u.email', 's.phone', 's.matric_number', 's.level', 's.enrolment_status',
                'i.name as institution', 'p.name as programme', 'l.name as lga',
            ]);
        abort_if($row === null || $row->status === 'draft', 404);

        $version = $this->latestVersion($application);
        abort_if($version === null, 404);

        $assessment = DB::table('need_assessments')->where('application_version_id', $version->id)->orderByDesc('id')->first();
        $assignment = DB::table('case_assignments as c')->join('users as u', 'u.id', '=', 'c.admin_id')
            ->where('c.application_id', $application)->whereNull('c.ended_at')->orderByDesc('c.id')
            ->first(['u.name as admin', 'c.started_at']);

        return Inertia::render('admin/application-review', [
            'application' => [
                'id' => $row->id,
                'reference' => $row->public_reference,
                'status' => $row->status,
                'submitted_at' => $row->submitted_at,
                'version_no' => $version->version_no,
            ],
            'student' => [
                'name' => $row->name, 'email' => $row->email, 'phone' => $row->phone,
                'matric_number' => $row->matric_number, 'level' => $row->level,
                'institution' => $row->institution, 'programme' => $row->programme,
                'lga' => $row->lga, 'enrolment_status' => $row->enrolment_status,
            ],
            'answers' => [
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
            ],
            'documents' => DB::table('application_documents as ad')
                ->join('documents as d', 'd.id', '=', 'ad.document_id')
                ->where('ad.application_version_id', $version->id)
                ->get(['d.id', 'ad.purpose', 'd.original_name as name', 'd.size_bytes as size', 'd.scan_status']),
            'assessment' => $this->assessmentView($assessment),
            'reviews' => DB::table('application_reviews as r')
                ->join('application_versions as v', 'v.id', '=', 'r.application_version_id')
                ->join('users as u', 'u.id', '=', 'r.reviewer_id')
                ->where('v.application_id', $application)->orderByDesc('r.id')
                ->get(['r.outcome', 'r.reason', 'r.created_at', 'u.name as reviewer']),
            'assignment' => $assignment,
            'indicators' => self::INDICATORS,
        ]);
    }

    public function start(Request $request, int $application): RedirectResponse
    {
        $admin = $request->user();

        $started = DB::transaction(function () use ($application, $admin) {
            $now = now();
            $changed = DB::table('applications')->where('id', $application)->where('status', 'submitted')->update([
                'status' => 'under_review',
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => $now,
            ]);

            if ($changed === 0) {
                return false;
            }

            DB::table('case_assignments')->insert([
                'application_id' => $application,
                'admin_id' => $admin->id,
                'assigned_by' => $admin->id,
                'started_at' => $now,
                'reason' => 'Review started',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            Audit::record('application.review_started', 'application', $application, $admin->id);

            return true;
        });

        return $started
            ? back()->with('status', 'Review started. Verify the evidence, then run the assessment.')
            : back()->with('error', 'This application is not waiting for review.');
    }

    public function assess(Request $request, NeedScorer $scorer, int $application): RedirectResponse
    {
        $admin = $request->user();

        $data = $request->validate([
            'verified' => ['required', 'array'],
            'verified.*' => ['boolean'],
            'risk_level' => ['nullable', Rule::in(['high', 'medium', 'low'])],
            'risk_reason' => ['nullable', 'required_with:risk_level', 'string', 'min:5', 'max:1000'],
        ]);

        if (! DB::table('applications')->where('id', $application)->where('status', 'under_review')->exists()) {
            return back()->with('error', 'Start the review before running an assessment.');
        }

        $version = $this->latestVersion($application);
        abort_if($version === null, 404);

        $verified = [];
        foreach (array_keys(self::INDICATORS) as $key) {
            $verified[$key] = (bool) data_get($data, "verified.{$key}", false);
        }

        // Only verified indicators count: an unverified one stays missing, never a zero.
        $inputs = new NeedInputs(
            monthlyIncome: $verified['income'] ? (int) $version->monthly_income : null,
            tuition: $verified['costs'] ? (int) $version->tuition : null,
            priorArrears: $verified['costs'] ? (int) $version->prior_arrears : null,
            externalAid: $verified['support'] ? (bool) $version->external_aid : null,
            dependants: $verified['dependants'] ? (int) $version->dependants : null,
            isOrphan: $verified['circumstances'] ? (bool) $version->is_orphan : null,
            singleParent: $verified['circumstances'] ? (bool) $version->single_parent : null,
            disability: $verified['circumstances'] ? (bool) $version->disability : null,
            riskLevel: $data['risk_level'] ?? null,
        );

        $policy = ScoringPolicies::current($admin->id);
        $result = $scorer->score($inputs, new ScoringPolicy((string) $policy->version, (int) $policy->minimum_score, (int) $policy->high_minimum));
        $complete = $result->status === 'complete';

        $unmet = null;
        if ($verified['costs']) {
            $aid = $verified['support'] && $version->external_aid ? (int) $version->external_aid_amount : 0;
            $unmet = max(0, (int) $version->tuition + (int) $version->prior_arrears - $aid);
        }

        $now = now();
        DB::transaction(function () use ($version, $policy, $admin, $complete, $inputs, $verified, $data, $result, $unmet, $now, $application) {
            DB::table('need_assessments')->insert([
                'application_version_id' => $version->id,
                'policy_id' => $policy->id,
                'assessor_id' => $admin->id,
                'status' => $complete ? 'complete' : 'pending',
                'assessed_at' => $complete ? $now : null,
                'verified_inputs' => json_encode(get_object_vars($inputs), JSON_THROW_ON_ERROR),
                'verification_map' => json_encode($verified, JSON_THROW_ON_ERROR),
                'risk_level' => $data['risk_level'] ?? null,
                'risk_reason' => $data['risk_reason'] ?? null,
                'income_points' => $result->points['income'],
                'costs_points' => $result->points['costs'],
                'support_points' => $result->points['support'],
                'dependant_points' => $result->points['dependants'],
                'circumstance_points' => $result->points['circumstances'],
                'risk_points' => $result->points['risk'],
                'total_score' => $result->total,
                'category' => $result->category,
                'verified_unmet_cost' => $unmet,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            Audit::record(
                action: 'application.assessed',
                subjectType: 'application',
                subjectId: $application,
                actorId: $admin->id,
                changes: ['status' => $complete ? 'complete' : 'pending', 'total' => $result->total, 'category' => $result->category, 'policy' => $policy->version],
            );
        });

        return back()->with('status', $complete
            ? "Assessment complete: {$result->total}/100 ({$result->category})."
            : 'Assessment saved as incomplete: verify every indicator and set a risk level to complete it.');
    }

    public function decide(Request $request, int $application): RedirectResponse
    {
        $admin = $request->user();
        $outcome = (string) $request->input('outcome');

        $data = $request->validate([
            'outcome' => ['required', Rule::in(['approved', 'declined', 'needs_information'])],
            'reason' => [
                Rule::requiredIf(in_array($outcome, ['declined', 'needs_information'], true)),
                'nullable', 'string', 'min:5', 'max:1000',
            ],
        ]);
        $reason = $data['reason'] ?? 'Evidence verified and enrolment confirmed.';

        $result = DB::transaction(function () use ($application, $data, $reason, $admin) {
            $locked = DB::table('applications')->where('id', $application)->lockForUpdate()->first();
            if ($locked === null || $locked->status !== 'under_review') {
                return 'not_under_review';
            }

            $version = $this->latestVersion($application);
            if ($version === null) {
                return 'not_under_review';
            }

            if ($data['outcome'] === 'approved') {
                $assessment = DB::table('need_assessments')->where('application_version_id', $version->id)->orderByDesc('id')->first();
                if ($assessment === null || $assessment->status !== 'complete') {
                    return 'assessment_incomplete';
                }
            }

            $now = now();
            DB::table('applications')->where('id', $application)->update([
                'status' => $data['outcome'],
                'row_version' => DB::raw('row_version + 1'),
                'updated_at' => $now,
            ]);
            DB::table('application_reviews')->insert([
                'application_version_id' => $version->id,
                'reviewer_id' => $admin->id,
                'outcome' => $data['outcome'],
                'reason' => $reason,
                'created_at' => $now,
            ]);
            DB::table('case_assignments')->where('application_id', $application)->whereNull('ended_at')
                ->update(['ended_at' => $now, 'updated_at' => $now]);

            $profile = DB::table('student_profiles')->where('id', $locked->student_id)->first();
            if ($profile === null) {
                return 'not_under_review';
            }

            if ($data['outcome'] === 'approved' && $profile->enrolment_status !== 'active') {
                DB::table('student_profiles')->where('id', $profile->id)->update([
                    'enrolment_status' => 'active',
                    'row_version' => DB::raw('row_version + 1'),
                    'updated_at' => $now,
                ]);
                DB::table('enrolment_events')->insert([
                    'student_id' => $profile->id,
                    'actor_id' => $admin->id,
                    'evidence_id' => null,
                    'from_status' => $profile->enrolment_status,
                    'to_status' => 'active',
                    'effective_at' => $now,
                    'reason' => 'Confirmed at application review',
                    'created_at' => $now,
                ]);
            }

            Audit::record(
                action: "application.{$data['outcome']}",
                subjectType: 'application',
                subjectId: $application,
                actorId: $admin->id,
                reason: $reason,
                changes: ['status' => ['from' => 'under_review', 'to' => $data['outcome']], 'version_no' => $version->version_no],
            );

            DB::table('notifications')->insert([
                'user_id' => $profile->user_id,
                'event_key' => "application.{$data['outcome']}:{$application}:{$version->version_no}",
                'kind' => "application.{$data['outcome']}",
                'payload' => json_encode(['outcome' => $data['outcome'], 'reason' => $reason], JSON_THROW_ON_ERROR),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return 'ok';
        });

        return match ($result) {
            'ok' => back()->with('status', 'Decision recorded: '.str_replace('_', ' ', $data['outcome']).'.'),
            'assessment_incomplete' => back()->with('error', 'Complete the need assessment (verify every indicator and set a risk level) before approving.'),
            default => back()->with('error', 'This application is not under review.'),
        };
    }

    public function document(Request $request, int $application, int $document): StreamedResponse
    {
        // Only evidence linked to this application's own versions can be opened.
        $file = DB::table('documents as d')
            ->join('application_documents as ad', 'ad.document_id', '=', 'd.id')
            ->join('application_versions as v', 'v.id', '=', 'ad.application_version_id')
            ->where('v.application_id', $application)
            ->where('d.id', $document)
            ->first(['d.storage_disk', 'd.object_key', 'd.original_name', 'd.mime_type']);
        abort_if($file === null, 404);

        Audit::record('document.viewed', 'document', $document, $request->user()->id, changes: ['application_id' => $application]);

        return Storage::disk($file->storage_disk)->download($file->object_key, $file->original_name, ['Content-Type' => $file->mime_type]);
    }

    private function latestVersion(int $applicationId): ?stdClass
    {
        return DB::table('application_versions')->where('application_id', $applicationId)->orderByDesc('version_no')->first();
    }

    /** @return array<string, mixed>|null */
    private function assessmentView(?stdClass $row): ?array
    {
        if ($row === null) {
            return null;
        }

        $map = (array) json_decode((string) $row->verification_map, true);
        $missing = [];
        foreach (self::INDICATORS as $key => $label) {
            if (empty($map[$key])) {
                $missing[] = $label;
            }
        }
        if ($row->risk_level === null) {
            $missing[] = 'Discontinuation risk';
        }

        return [
            'status' => $row->status,
            'points' => [
                'income' => $row->income_points,
                'costs' => $row->costs_points,
                'support' => $row->support_points,
                'dependants' => $row->dependant_points,
                'circumstances' => $row->circumstance_points,
                'risk' => $row->risk_points,
            ],
            'total' => $row->total_score,
            'category' => $row->category,
            'risk_level' => $row->risk_level,
            'risk_reason' => $row->risk_reason,
            'verified' => $map,
            'missing' => $missing,
            'unmet_cost' => $row->verified_unmet_cost === null ? null : intdiv((int) $row->verified_unmet_cost, 100),
            'assessed_at' => $row->assessed_at,
        ];
    }
}
