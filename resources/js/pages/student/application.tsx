import { Head, Link, router, useForm } from '@inertiajs/react';
import { BadgeCheck } from 'lucide-react';
import { useState } from 'react';
import FlashMessages from '@/components/flash-messages';
import InputError from '@/components/input-error';
import NativeSelect from '@/components/native-select';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';

type Doc = { id: number; purpose: string; name: string; size: number };
type Draft = Record<string, string | number | boolean | null>;

type Props = {
    state: 'profile_incomplete' | 'none' | 'draft' | 'submitted';
    missing_profile: string[];
    application: {
        reference: string;
        status: string;
        submitted_at: string | null;
    } | null;
    draft: Draft | null;
    documents: Doc[];
    submitted: {
        version_no: number;
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
        declared_at: string;
    } | null;
    purposes: string[];
};

const purposeLabels: Record<string, string> = {
    academic_result: 'Semester result',
    admission_letter: 'Admission letter',
    income: 'Household income evidence',
    tuition: 'Tuition / fees evidence',
    other: 'Other supporting document',
};

const str = (v: string | number | boolean | null | undefined) =>
    v === null || v === undefined ? '' : String(v);
const yn = (v: unknown) =>
    v === true || v === '1' || v === 1
        ? '1'
        : v === false || v === '0' || v === 0
          ? '0'
          : '';
const naira = (v: number | null) =>
    v === null ? '-' : `₦${Number(v).toLocaleString()}`;
const yesNo = (v: boolean) => (v ? 'Yes' : 'No');
const kb = (bytes: number) => `${Math.max(1, Math.round(bytes / 1024))} KB`;

function Field({
    label,
    hint,
    error,
    children,
}: {
    label: string;
    hint?: string;
    error?: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-2">
            <Label>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

function YesNo({
    name,
    value,
    onChange,
    disabled,
}: {
    name: string;
    value: string;
    onChange: (v: string) => void;
    disabled?: boolean;
}) {
    return (
        <div className="flex gap-5">
            {['1', '0'].map((v) => (
                <label key={v} className="flex items-center gap-2 text-sm">
                    <input
                        type="radio"
                        name={name}
                        value={v}
                        checked={value === v}
                        disabled={disabled}
                        onChange={() => onChange(v)}
                        className="accent-primary"
                    />
                    {v === '1' ? 'Yes' : 'No'}
                </label>
            ))}
        </div>
    );
}

export default function StudentApplication(props: Props) {
    const {
        state,
        missing_profile,
        application,
        draft,
        documents,
        submitted,
        purposes,
    } = props;

    const form = useForm({
        monthly_income: str(draft?.monthly_income),
        tuition: str(draft?.tuition),
        prior_arrears: str(draft?.prior_arrears),
        external_aid: yn(draft?.external_aid),
        external_aid_amount: str(draft?.external_aid_amount),
        dependants: str(draft?.dependants),
        is_orphan: yn(draft?.is_orphan),
        single_parent: yn(draft?.single_parent),
        disability: yn(draft?.disability),
        result_availability: str(draft?.result_availability),
    });

    const upload = useForm<{ file: File | null; purpose: string }>({
        file: null,
        purpose: 'academic_result',
    });
    const [fileKey, setFileKey] = useState(0);

    const errors = form.errors as Record<string, string | undefined>;

    return (
        <>
            <Head title="My application" />
            <div className="flex max-w-3xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">My application</h1>
                    {application && (
                        <Badge variant="secondary">
                            {application.status.replaceAll('_', ' ')}
                        </Badge>
                    )}
                </div>

                {state === 'submitted' && application ? (
                    <Alert className="border-primary bg-accent">
                        <BadgeCheck aria-hidden="true" />
                        <AlertTitle>
                            {application.status === 'submitted'
                                ? 'Application submitted successfully'
                                : `Application ${application.status.replaceAll('_', ' ')}`}
                        </AlertTitle>
                        <AlertDescription>
                            <p>
                                Your reference is{' '}
                                <strong>{application.reference}</strong>. Keep
                                it for any enquiry.
                            </p>
                            <p>
                                {application.status === 'submitted'
                                    ? 'An HCM Foundation Admin will review it and you will be notified of the decision. Your answers are now locked.'
                                    : 'Your answers are locked while it is processed.'}
                            </p>
                        </AlertDescription>
                    </Alert>
                ) : (
                    <FlashMessages />
                )}

                {state === 'profile_incomplete' && (
                    <Alert>
                        <AlertTitle>Complete your profile first</AlertTitle>
                        <AlertDescription>
                            <p>Still needed: {missing_profile.join(', ')}.</p>
                            <Button asChild className="mt-3">
                                <Link href="/student/profile">
                                    Go to my profile
                                </Link>
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}

                {state === 'none' && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Start your application</CardTitle>
                            <CardDescription>
                                You will describe your household situation and
                                upload your latest semester result. Nothing is
                                shared with donors that identifies you.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button
                                onClick={() =>
                                    router.post('/student/application/start')
                                }
                            >
                                Start application
                            </Button>
                        </CardContent>
                    </Card>
                )}

                {state === 'draft' && (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Household and costs</CardTitle>
                                <CardDescription>
                                    Amounts are in naira. Answer honestly:
                                    evidence is checked by an Admin.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-5 md:grid-cols-2">
                                <Field
                                    label="Total monthly household income (₦)"
                                    error={errors.monthly_income}
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        step={1}
                                        inputMode="numeric"
                                        value={form.data.monthly_income}
                                        onChange={(e) =>
                                            form.setData(
                                                'monthly_income',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Tuition and fees this period (₦)"
                                    error={errors.tuition}
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        step={1}
                                        inputMode="numeric"
                                        value={form.data.tuition}
                                        onChange={(e) =>
                                            form.setData(
                                                'tuition',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Unpaid fees from earlier periods (₦)"
                                    hint="Enter 0 if none."
                                    error={errors.prior_arrears}
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        step={1}
                                        inputMode="numeric"
                                        value={form.data.prior_arrears}
                                        onChange={(e) =>
                                            form.setData(
                                                'prior_arrears',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="People who depend on the household"
                                    error={errors.dependants}
                                >
                                    <Input
                                        type="number"
                                        min={0}
                                        max={30}
                                        step={1}
                                        inputMode="numeric"
                                        value={form.data.dependants}
                                        onChange={(e) =>
                                            form.setData(
                                                'dependants',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Do you receive other financial aid?"
                                    error={errors.external_aid}
                                >
                                    <YesNo
                                        name="external_aid"
                                        value={form.data.external_aid}
                                        onChange={(v) =>
                                            form.setData('external_aid', v)
                                        }
                                    />
                                </Field>
                                {form.data.external_aid === '1' && (
                                    <Field
                                        label="Amount of other aid (₦)"
                                        error={errors.external_aid_amount}
                                    >
                                        <Input
                                            type="number"
                                            min={0}
                                            step={1}
                                            inputMode="numeric"
                                            value={
                                                form.data.external_aid_amount
                                            }
                                            onChange={(e) =>
                                                form.setData(
                                                    'external_aid_amount',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </Field>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Your circumstances</CardTitle>
                                <CardDescription>
                                    Each answer must be supported if an Admin
                                    asks.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-5 md:grid-cols-3">
                                <Field label="Orphan?" error={errors.is_orphan}>
                                    <YesNo
                                        name="is_orphan"
                                        value={form.data.is_orphan}
                                        onChange={(v) =>
                                            form.setData('is_orphan', v)
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Single-parent household?"
                                    error={errors.single_parent}
                                >
                                    <YesNo
                                        name="single_parent"
                                        value={form.data.single_parent}
                                        onChange={(v) =>
                                            form.setData('single_parent', v)
                                        }
                                    />
                                </Field>
                                <Field
                                    label="Living with a disability?"
                                    error={errors.disability}
                                >
                                    <YesNo
                                        name="disability"
                                        value={form.data.disability}
                                        onChange={(v) =>
                                            form.setData('disability', v)
                                        }
                                    />
                                </Field>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Academic evidence</CardTitle>
                                <CardDescription>
                                    New entrants without results can choose "Not
                                    yet available" and upload their admission
                                    letter.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-5">
                                <Field
                                    label="Semester result"
                                    error={errors.result_availability}
                                >
                                    <NativeSelect
                                        value={form.data.result_availability}
                                        onChange={(e) =>
                                            form.setData(
                                                'result_availability',
                                                e.target.value,
                                            )
                                        }
                                    >
                                        <option value="">Select</option>
                                        <option value="available">
                                            Available
                                        </option>
                                        <option value="not_yet_available">
                                            Not yet available (new entrant)
                                        </option>
                                    </NativeSelect>
                                </Field>

                                <div className="grid gap-3 rounded-lg border p-3">
                                    <div className="text-sm font-medium">
                                        Upload a document
                                    </div>
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <NativeSelect
                                            value={upload.data.purpose}
                                            onChange={(e) =>
                                                upload.setData(
                                                    'purpose',
                                                    e.target.value,
                                                )
                                            }
                                        >
                                            {purposes.map((p) => (
                                                <option key={p} value={p}>
                                                    {purposeLabels[p] ?? p}
                                                </option>
                                            ))}
                                        </NativeSelect>
                                        <Input
                                            key={fileKey}
                                            type="file"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            onChange={(e) =>
                                                upload.setData(
                                                    'file',
                                                    e.target.files?.[0] ?? null,
                                                )
                                            }
                                        />
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        PDF, JPG or PNG, up to 5 MB. Stored
                                        privately.
                                    </p>
                                    <InputError
                                        message={
                                            upload.errors.file ??
                                            upload.errors.purpose
                                        }
                                    />
                                    <div>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            disabled={
                                                upload.processing ||
                                                upload.data.file === null
                                            }
                                            onClick={() =>
                                                upload.post(
                                                    '/student/application/documents',
                                                    {
                                                        forceFormData: true,
                                                        preserveScroll: true,
                                                        onSuccess: () => {
                                                            upload.reset(
                                                                'file',
                                                            );
                                                            setFileKey(
                                                                (k) => k + 1,
                                                            );
                                                        },
                                                    },
                                                )
                                            }
                                        >
                                            {upload.processing && <Spinner />}
                                            Upload
                                        </Button>
                                    </div>
                                </div>

                                <InputError message={errors.documents} />
                                <InputError message={errors.application} />

                                {documents.length > 0 && (
                                    <ul className="grid gap-2">
                                        {documents.map((d) => (
                                            <li
                                                key={d.id}
                                                className="flex items-center justify-between gap-3 rounded-lg border p-2 text-sm"
                                            >
                                                <span>
                                                    <span className="font-medium">
                                                        {purposeLabels[
                                                            d.purpose
                                                        ] ?? d.purpose}
                                                    </span>
                                                    <span className="text-muted-foreground">
                                                        {' '}
                                                        · {d.name} ·{' '}
                                                        {kb(d.size)}
                                                    </span>
                                                </span>
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        router.delete(
                                                            `/student/application/documents/${d.id}`,
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <div className="flex flex-wrap gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.processing}
                                onClick={() =>
                                    form.put('/student/application/draft', {
                                        preserveScroll: true,
                                    })
                                }
                            >
                                Save draft
                            </Button>
                            <Button
                                type="button"
                                disabled={form.processing}
                                onClick={() =>
                                    form.post('/student/application/submit', {
                                        // keep the position only when there are errors to fix; on success go to the top
                                        preserveScroll: 'errors',
                                    })
                                }
                            >
                                {form.processing && <Spinner />}
                                Submit application
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            After you submit, your answers are locked and an
                            Admin reviews them.
                        </p>
                    </>
                )}

                {state === 'submitted' && application && submitted && (
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Application {application.reference}
                            </CardTitle>
                            <CardDescription>
                                Submitted{' '}
                                {application.submitted_at
                                    ? new Date(
                                          application.submitted_at,
                                      ).toLocaleString()
                                    : ''}{' '}
                                · version {submitted.version_no}. Your answers
                                are locked.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-2 text-sm md:grid-cols-2">
                            <div>
                                Monthly household income:{' '}
                                <strong>
                                    {naira(submitted.monthly_income)}
                                </strong>
                            </div>
                            <div>
                                Tuition and fees:{' '}
                                <strong>{naira(submitted.tuition)}</strong>
                            </div>
                            <div>
                                Earlier unpaid fees:{' '}
                                <strong>
                                    {naira(submitted.prior_arrears)}
                                </strong>
                            </div>
                            <div>
                                Dependants:{' '}
                                <strong>{submitted.dependants}</strong>
                            </div>
                            <div>
                                Other aid:{' '}
                                <strong>
                                    {yesNo(submitted.external_aid)}
                                    {submitted.external_aid
                                        ? ` (${naira(submitted.external_aid_amount)})`
                                        : ''}
                                </strong>
                            </div>
                            <div>
                                Orphan:{' '}
                                <strong>{yesNo(submitted.is_orphan)}</strong>
                            </div>
                            <div>
                                Single-parent household:{' '}
                                <strong>
                                    {yesNo(submitted.single_parent)}
                                </strong>
                            </div>
                            <div>
                                Disability:{' '}
                                <strong>{yesNo(submitted.disability)}</strong>
                            </div>
                            <div className="md:col-span-2">
                                Documents:{' '}
                                {documents
                                    .map(
                                        (d) =>
                                            `${purposeLabels[d.purpose] ?? d.purpose} (${d.name})`,
                                    )
                                    .join('; ')}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

StudentApplication.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'My application', href: '/student/application' },
    ],
};
