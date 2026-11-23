import { Head, Link, usePage } from '@inertiajs/react';
import {
    BadgeCheck,
    ClipboardCheck,
    ClipboardList,
    Clock,
    FileText,
    GraduationCap,
    HeartHandshake,
    ShieldCheck,
    UserCheck,
    Wallet,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
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
import { dashboard } from '@/routes';
import type { Role } from '@/types/auth';

type Summary = Record<string, number | string | null>;

const roleLabels: Record<Role, string> = {
    student: 'Student',
    donor: 'Donor',
    admin: 'Admin (HCM Foundation)',
    super_admin: 'Super Admin',
    none: 'No role',
};

function Stat({
    title,
    value,
    hint,
    href,
    icon: Icon,
}: {
    title: string;
    value: string | number;
    hint?: string;
    href?: string;
    icon: LucideIcon;
}) {
    const card = (
        <Card
            className={href ? 'transition-colors hover:bg-accent' : undefined}
        >
            <CardHeader>
                <div className="flex items-start justify-between gap-3">
                    <div className="grid gap-1">
                        <CardDescription>{title}</CardDescription>
                        <CardTitle className="text-3xl capitalize">
                            {value}
                        </CardTitle>
                    </div>
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground">
                        <Icon className="size-5" aria-hidden="true" />
                    </div>
                </div>
            </CardHeader>
            {hint && (
                <CardContent className="text-sm text-muted-foreground">
                    {hint}
                </CardContent>
            )}
        </Card>
    );

    return href ? <Link href={href}>{card}</Link> : card;
}

function StudentView({ summary }: { summary: Summary }) {
    return (
        <>
            <div className="grid gap-4 md:grid-cols-3">
                <Stat
                    icon={GraduationCap}
                    title="Enrolment status"
                    value={String(summary.enrolment_status)}
                    hint="Confirmed by an Admin after your application is reviewed."
                />
                <Stat
                    icon={FileText}
                    title="Applications submitted"
                    value={Number(summary.applications)}
                    hint="Drafts are not counted until you submit."
                />
                <Stat
                    icon={ClipboardCheck}
                    title="Application status"
                    value={
                        summary.application_status
                            ? String(summary.application_status).replaceAll(
                                  '_',
                                  ' ',
                              )
                            : 'Not started'
                    }
                    hint="Where your application stands right now."
                />
            </div>
            <div className="flex flex-wrap gap-3">
                <Button asChild>
                    <Link href="/student/application">
                        {summary.application_status === 'draft'
                            ? 'Continue my draft'
                            : summary.application_status
                              ? 'View my application'
                              : 'Start my application'}
                    </Link>
                </Button>
                <Button asChild variant="outline">
                    <Link href="/student/profile">Complete my profile</Link>
                </Button>
            </div>
            <Card>
                <CardHeader>
                    <CardTitle>How applying works</CardTitle>
                    <CardDescription>
                        Your information is used only to assess your need and
                        match you to a fund.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <ol className="list-decimal space-y-1 pl-5 text-sm">
                        <li>
                            Complete your profile (institution, programme,
                            level).
                        </li>
                        <li>Upload your latest semester result.</li>
                        <li>
                            Submit your application with supporting evidence.
                        </li>
                        <li>
                            An Admin reviews it and you are notified of the
                            decision.
                        </li>
                    </ol>
                </CardContent>
            </Card>
        </>
    );
}

function DonorView({ summary }: { summary: Summary }) {
    const approved = summary.approval_status === 'approved';

    if (!approved) {
        const declined = summary.approval_status === 'declined';

        return (
            <Alert>
                <Clock aria-hidden="true" />
                <AlertTitle>
                    {declined
                        ? 'Your donor registration was not approved'
                        : 'Awaiting Admin approval'}
                </AlertTitle>
                <AlertDescription>
                    {declined
                        ? (summary.review_reason ??
                          'Please contact HCM Foundation for details.')
                        : 'An HCM Foundation Admin must verify your registration before you can create a fund or make a contribution. You will be notified once it is reviewed.'}
                </AlertDescription>
            </Alert>
        );
    }

    return (
        <div className="grid gap-4 md:grid-cols-3">
            <Stat
                icon={Wallet}
                title="Your funds"
                value={Number(summary.funds)}
                hint="Funds you have set up with eligibility criteria."
            />
            <Stat
                icon={BadgeCheck}
                title="Registration"
                value="Approved"
                hint="You can create funds and contribute."
            />
        </div>
    );
}

function AdminView({ summary }: { summary: Summary }) {
    return (
        <div className="grid gap-4 md:grid-cols-3">
            <Stat
                icon={UserCheck}
                title="Donors awaiting approval"
                value={Number(summary.pending_donors)}
                hint="Only verified donors can pay into a fund."
                href="/admin/donors"
            />
            <Stat
                icon={ClipboardList}
                title="Applications to review"
                value={Number(summary.awaiting_review)}
                hint="Submitted and waiting for an Admin decision."
                href="/admin/applications"
            />
        </div>
    );
}

function SuperAdminView({ summary }: { summary: Summary }) {
    return (
        <div className="grid gap-4 md:grid-cols-3">
            <Stat
                icon={ShieldCheck}
                title="Admin accounts"
                value={Number(summary.admins)}
            />
            <Stat
                icon={GraduationCap}
                title="Students"
                value={Number(summary.students)}
            />
            <Stat
                icon={HeartHandshake}
                title="Donors"
                value={Number(summary.donors)}
            />
        </div>
    );
}

export default function Dashboard({
    role,
    summary,
}: {
    role: Role;
    summary: Summary;
}) {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">
                        Welcome, {auth.user.name}
                    </h1>
                    <Badge variant="secondary">{roleLabels[role]}</Badge>
                </div>

                {role === 'student' && <StudentView summary={summary} />}
                {role === 'donor' && <DonorView summary={summary} />}
                {role === 'admin' && <AdminView summary={summary} />}
                {role === 'super_admin' && <SuperAdminView summary={summary} />}
                {role === 'none' && (
                    <Alert>
                        <AlertTitle>No role assigned</AlertTitle>
                        <AlertDescription>
                            Your account has no role yet. Please contact the
                            platform administrator.
                        </AlertDescription>
                    </Alert>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
