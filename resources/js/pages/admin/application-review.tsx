import { Head, Link, router, useForm } from '@inertiajs/react';
import FlashMessages from '@/components/flash-messages';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';

type Doc = {
    id: number;
    purpose: string;
    name: string;
    size: number;
    scan_status: string;
};

type Assessment = {
    status: string;
    points: Record<string, number | null>;
    total: number | null;
    category: string | null;
    risk_level: string | null;
    risk_reason: string | null;
    verified: Record<string, boolean>;
    missing: string[];
    unmet_cost: number | null;
    assessed_at: string | null;
};

type Props = {
    application: {
        id: number;
        reference: string;
        status: string;
        submitted_at: string | null;
        version_no: number;
    };
    student: {
        name: string;
        email: string;
        phone: string | null;
        matric_number: string | null;
        level: number | null;
        institution: string | null;
        programme: string | null;
        lga: string | null;
        enrolment_status: string;
    };
    answers: {
        monthly_income: number;
        tuition: number;
        prior_arrears: number;
        external_aid: boolean;
        external_aid_amount: number | null;
        dependants: number;
        is_orphan: boolean;
        single_parent: boolean;
        disability: boolean;
        result_availability: string;
    };
    documents: Doc[];
    assessment: Assessment | null;
    reviews: {
        outcome: string;
        reason: string;
        created_at: string;
        reviewer: string;
    }[];
    assignment: { admin: string; started_at: string } | null;
    indicators: Record<string, string>;
};

const purposeLabels: Record<string, string> = {
    academic_result: 'Semester result',
    admission_letter: 'Admission letter',
    income: 'Household income evidence',
    tuition: 'Tuition / fees evidence',
    other: 'Other supporting document',
};

const maxPoints: Record<string, number> = {
    income: 30,
    costs: 25,
    support: 15,
    dependants: 10,
    circumstances: 10,
    risk: 10,
};
const pointLabels: Record<string, string> = {
    income: 'Household income',
    costs: 'Tuition and arrears',
    support: 'Other aid',
    dependants: 'Dependants',
    circumstances: 'Circumstances',
    risk: 'Discontinuation risk',
};

const naira = (v: number | null) =>
    v === null ? '-' : `₦${Number(v).toLocaleString()}`;
const yesNo = (v: boolean) => (v ? 'Yes' : 'No');
const kb = (bytes: number) => `${Math.max(1, Math.round(bytes / 1024))} KB`;

export default function ApplicationReview({
    application,
    student,
    answers,
    documents,
    assessment,
    reviews,
    assignment,
    indicators,
}: Props) {
    const underReview = application.status === 'under_review';
    const base = `/admin/applications/${application.id}`;

    const assess = useForm({
        income: assessment?.verified?.income ?? false,
        costs: assessment?.verified?.costs ?? false,
        support: assessment?.verified?.support ?? false,
        dependants: assessment?.verified?.dependants ?? false,
        circumstances: assessment?.verified?.circumstances ?? false,
        risk_level: assessment?.risk_level ?? '',
        risk_reason: assessment?.risk_reason ?? '',
    });
    const assessErrors = assess.errors as Record<string, string | undefined>;

    const decision = useForm({ outcome: '', reason: '' });

    const runAssessment = () => {
        assess.transform((d) => ({
            verified: {
                income: d.income ? 1 : 0,
                costs: d.costs ? 1 : 0,
                support: d.support ? 1 : 0,
                dependants: d.dependants ? 1 : 0,
                circumstances: d.circumstances ? 1 : 0,
            },
            risk_level: d.risk_level || null,
            risk_reason: d.risk_reason || null,
        }));
        assess.post(`${base}/assessment`, { preserveScroll: true });
    };

    const decide = (outcome: string) => {
        if (
            !window.confirm(
                'Record this decision? It cannot be undone from this screen.',
            )
        ) {
            return;
        }
        decision.transform((d) => ({ ...d, outcome }));
        decision.post(`${base}/decision`, { preserveScroll: 'errors' });
    };

    const checkbox = (
        key: 'income' | 'costs' | 'support' | 'dependants' | 'circumstances',
    ) => (
        <label key={key} className="flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                className="accent-primary"
                checked={assess.data[key]}
                onChange={(e) => assess.setData(key, e.target.checked)}
            />
            I verified the evidence for: <strong>{indicators[key]}</strong>
        </label>
    );

    return (
        <>
            <Head title="Review application" />
            <div className="flex max-w-4xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <Button asChild size="sm" variant="outline">
                        <Link href="/admin/applications">Back</Link>
                    </Button>
                    <h1 className="text-2xl font-semibold">
                        Application …{application.reference.slice(-8)}
                    </h1>
                    <Badge variant="secondary">
                        {application.status.replaceAll('_', ' ')}
                    </Badge>
                    <span className="text-xs text-muted-foreground">
                        version {application.version_no}
                    </span>
                </div>

                <FlashMessages />

                {application.status === 'submitted' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Waiting for review</CardTitle>
                            <CardDescription>
                                Starting the review assigns this case to you and
                                unlocks the assessment.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                onClick={() =>
                                    router.post(
                                        `${base}/start`,
                                        {},
                                        { preserveScroll: true },
                                    )
                                }
                            >
                                Start review
                            </Button>
                        </CardContent>
                    </Card>
                )}
                {assignment && underReview && (
                    <p className="text-sm text-muted-foreground">
                        Assigned to {assignment.admin} since{' '}
                        {new Date(assignment.started_at).toLocaleString()}.
                    </p>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Student</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-2 text-sm md:grid-cols-2">
                        <div>
                            Name: <strong>{student.name}</strong>
                        </div>
                        <div>
                            Email: <strong>{student.email}</strong>
                        </div>
                        <div>
                            Phone: <strong>{student.phone ?? '-'}</strong>
                        </div>
                        <div>
                            Matric number:{' '}
                            <strong>{student.matric_number ?? '-'}</strong>
                        </div>
                        <div>
                            Institution:{' '}
                            <strong>{student.institution ?? '-'}</strong>
                        </div>
                        <div>
                            Programme:{' '}
                            <strong>{student.programme ?? '-'}</strong>
                        </div>
                        <div>
                            Level: <strong>{student.level ?? '-'}</strong>
                        </div>
                        <div>
                            Local government:{' '}
                            <strong>{student.lga ?? '-'}</strong>
                        </div>
                        <div>
                            Enrolment:{' '}
                            <strong>{student.enrolment_status}</strong>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>What the student declared</CardTitle>
                        <CardDescription>
                            Declared answers. Nothing is scored until you verify
                            the evidence.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-2 text-sm md:grid-cols-2">
                        <div>
                            Monthly household income:{' '}
                            <strong>{naira(answers.monthly_income)}</strong>
                        </div>
                        <div>
                            Tuition and fees:{' '}
                            <strong>{naira(answers.tuition)}</strong>
                        </div>
                        <div>
                            Earlier unpaid fees:{' '}
                            <strong>{naira(answers.prior_arrears)}</strong>
                        </div>
                        <div>
                            Dependants: <strong>{answers.dependants}</strong>
                        </div>
                        <div>
                            Other aid:{' '}
                            <strong>
                                {yesNo(answers.external_aid)}
                                {answers.external_aid
                                    ? ` (${naira(answers.external_aid_amount)})`
                                    : ''}
                            </strong>
                        </div>
                        <div>
                            Orphan: <strong>{yesNo(answers.is_orphan)}</strong>
                        </div>
                        <div>
                            Single-parent household:{' '}
                            <strong>{yesNo(answers.single_parent)}</strong>
                        </div>
                        <div>
                            Disability:{' '}
                            <strong>{yesNo(answers.disability)}</strong>
                        </div>
                        <div>
                            Semester result:{' '}
                            <strong>
                                {answers.result_availability.replaceAll(
                                    '_',
                                    ' ',
                                )}
                            </strong>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Evidence</CardTitle>
                        <CardDescription>
                            Private files. Each time you open one it is recorded
                            in the audit log.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {documents.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                No documents.
                            </p>
                        )}
                        <ul className="grid gap-2">
                            {documents.map((d) => (
                                <li
                                    key={d.id}
                                    className="flex flex-wrap items-center justify-between gap-2 rounded-lg border p-2 text-sm"
                                >
                                    <span>
                                        <span className="font-medium">
                                            {purposeLabels[d.purpose] ??
                                                d.purpose}
                                        </span>
                                        <span className="text-muted-foreground">
                                            {' '}
                                            · {d.name} · {kb(d.size)}
                                        </span>
                                    </span>
                                    <span className="flex items-center gap-2">
                                        <Badge variant="outline">
                                            scan {d.scan_status}
                                        </Badge>
                                        <Button
                                            asChild
                                            size="sm"
                                            variant="outline"
                                        >
                                            <a
                                                href={`${base}/documents/${d.id}`}
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                Open
                                            </a>
                                        </Button>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>

                {(underReview || assessment) && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Need assessment</CardTitle>
                            <CardDescription>
                                Only verified indicators are scored. A missing
                                one keeps the assessment incomplete; it is never
                                counted as zero.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-4">
                            {underReview && (
                                <div className="grid gap-3 rounded-lg border p-3">
                                    {checkbox('income')}
                                    {checkbox('costs')}
                                    {checkbox('support')}
                                    {checkbox('dependants')}
                                    {checkbox('circumstances')}
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <div className="grid gap-1">
                                            <label
                                                className="text-sm font-medium"
                                                htmlFor="risk_level"
                                            >
                                                Discontinuation risk (Admin
                                                judgement)
                                            </label>
                                            <NativeSelect
                                                id="risk_level"
                                                value={assess.data.risk_level}
                                                onChange={(e) =>
                                                    assess.setData(
                                                        'risk_level',
                                                        e.target.value,
                                                    )
                                                }
                                            >
                                                <option value="">
                                                    Not assessed yet
                                                </option>
                                                <option value="high">
                                                    High
                                                </option>
                                                <option value="medium">
                                                    Medium
                                                </option>
                                                <option value="low">Low</option>
                                            </NativeSelect>
                                            <InputError
                                                message={
                                                    assessErrors.risk_level
                                                }
                                            />
                                        </div>
                                        <div className="grid gap-1">
                                            <label
                                                className="text-sm font-medium"
                                                htmlFor="risk_reason"
                                            >
                                                Reason for the risk rating
                                            </label>
                                            <textarea
                                                id="risk_reason"
                                                rows={2}
                                                className="w-full rounded-md border bg-transparent p-2 text-sm"
                                                value={assess.data.risk_reason}
                                                onChange={(e) =>
                                                    assess.setData(
                                                        'risk_reason',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={
                                                    assessErrors.risk_reason
                                                }
                                            />
                                        </div>
                                    </div>
                                    <div>
                                        <Button
                                            type="button"
                                            disabled={assess.processing}
                                            onClick={runAssessment}
                                        >
                                            {assess.processing && <Spinner />}
                                            Run assessment
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {assessment && (
                                <div className="grid gap-2 text-sm">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <strong>Latest result:</strong>
                                        <Badge
                                            variant={
                                                assessment.status === 'complete'
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                        >
                                            {assessment.status}
                                        </Badge>
                                        {assessment.category && (
                                            <Badge variant="secondary">
                                                {assessment.category} need
                                            </Badge>
                                        )}
                                    </div>
                                    <table className="w-full max-w-md">
                                        <tbody>
                                            {Object.keys(pointLabels).map(
                                                (key) => (
                                                    <tr
                                                        key={key}
                                                        className="border-b last:border-0"
                                                    >
                                                        <td className="py-1">
                                                            {pointLabels[key]}
                                                        </td>
                                                        <td className="py-1 text-right">
                                                            {assessment.points[
                                                                key
                                                            ] === null ||
                                                            assessment.points[
                                                                key
                                                            ] === undefined
                                                                ? 'not verified'
                                                                : `${assessment.points[key]} / ${maxPoints[key]}`}
                                                        </td>
                                                    </tr>
                                                ),
                                            )}
                                            <tr>
                                                <td className="py-1 font-semibold">
                                                    Total
                                                </td>
                                                <td className="py-1 text-right font-semibold">
                                                    {assessment.total === null
                                                        ? 'incomplete'
                                                        : `${assessment.total} / 100`}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    {assessment.missing.length > 0 && (
                                        <p className="text-muted-foreground">
                                            Still needed:{' '}
                                            {assessment.missing.join(', ')}.
                                        </p>
                                    )}
                                    {assessment.unmet_cost !== null && (
                                        <p className="text-muted-foreground">
                                            Verified unmet cost:{' '}
                                            {naira(assessment.unmet_cost)}
                                        </p>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {underReview && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Decision</CardTitle>
                            <CardDescription>
                                Approving confirms the student&apos;s enrolment
                                and requires a complete assessment. Declining or
                                asking for more information needs a reason the
                                student will see.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-3">
                            <textarea
                                rows={3}
                                className="w-full rounded-md border bg-transparent p-2 text-sm"
                                placeholder="Reason or note for the student"
                                value={decision.data.reason}
                                onChange={(e) =>
                                    decision.setData('reason', e.target.value)
                                }
                            />
                            <InputError
                                message={
                                    (
                                        decision.errors as Record<
                                            string,
                                            string | undefined
                                        >
                                    ).reason
                                }
                            />
                            <div className="flex flex-wrap gap-3">
                                <Button
                                    disabled={decision.processing}
                                    onClick={() => decide('approved')}
                                >
                                    Approve
                                </Button>
                                <Button
                                    variant="outline"
                                    disabled={decision.processing}
                                    onClick={() => decide('needs_information')}
                                >
                                    Ask for more information
                                </Button>
                                <Button
                                    variant="destructive"
                                    disabled={decision.processing}
                                    onClick={() => decide('declined')}
                                >
                                    Decline
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {reviews.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Decision history</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm">
                            {reviews.map((r) => (
                                <div
                                    key={`${r.created_at}-${r.outcome}`}
                                    className="rounded-lg border p-2"
                                >
                                    <strong>
                                        {r.outcome.replaceAll('_', ' ')}
                                    </strong>{' '}
                                    by {r.reviewer} on{' '}
                                    {new Date(r.created_at).toLocaleString()}:{' '}
                                    {r.reason}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

ApplicationReview.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Applications', href: '/admin/applications' },
        { title: 'Review', href: '#' },
    ],
};
