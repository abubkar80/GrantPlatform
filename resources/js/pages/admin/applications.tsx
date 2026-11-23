import { Head, Link } from '@inertiajs/react';
import FlashMessages from '@/components/flash-messages';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { dashboard } from '@/routes';

type Row = {
    id: number;
    public_reference: string;
    status: string;
    submitted_at: string | null;
    name: string;
    institution: string | null;
    programme: string | null;
};

type Filter =
    | 'submitted'
    | 'under_review'
    | 'needs_information'
    | 'approved'
    | 'declined'
    | 'all';

type Props = {
    applications: Row[];
    filter: Filter;
    counts: Record<string, number>;
};

const tabs: { key: Filter; label: string }[] = [
    { key: 'submitted', label: 'Waiting' },
    { key: 'under_review', label: 'Under review' },
    { key: 'needs_information', label: 'Needs information' },
    { key: 'approved', label: 'Approved' },
    { key: 'declined', label: 'Declined' },
    { key: 'all', label: 'All' },
];

const statusVariant = (status: string) => {
    switch (status) {
        case 'approved':
            return 'default' as const;
        case 'declined':
            return 'destructive' as const;
        case 'needs_information':
            return 'outline' as const;
        default:
            return 'secondary' as const;
    }
};

export default function Applications({ applications, filter, counts }: Props) {
    return (
        <>
            <Head title="Applications" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Applications</h1>
                    <p className="text-sm text-muted-foreground">
                        Review submitted applications, verify the evidence and
                        record a decision. Every step is audited.
                    </p>
                </div>

                <FlashMessages />

                <div className="flex flex-wrap gap-2">
                    {tabs.map((tab) => (
                        <Button
                            key={tab.key}
                            asChild
                            size="sm"
                            variant={filter === tab.key ? 'default' : 'outline'}
                        >
                            <Link
                                href={`/admin/applications?status=${tab.key}`}
                            >
                                {tab.label}
                                {tab.key !== 'all' &&
                                    ` (${counts[tab.key] ?? 0})`}
                            </Link>
                        </Button>
                    ))}
                </div>

                <Card>
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead className="border-b text-left text-muted-foreground">
                                <tr>
                                    <th className="p-3 font-medium">
                                        Reference
                                    </th>
                                    <th className="p-3 font-medium">Student</th>
                                    <th className="p-3 font-medium">
                                        Institution / programme
                                    </th>
                                    <th className="p-3 font-medium">
                                        Submitted
                                    </th>
                                    <th className="p-3 font-medium">Status</th>
                                    <th className="p-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {applications.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="p-6 text-center text-muted-foreground"
                                        >
                                            No applications in this view.
                                        </td>
                                    </tr>
                                )}
                                {applications.map((a) => (
                                    <tr
                                        key={a.id}
                                        className="border-b last:border-0"
                                    >
                                        <td
                                            className="p-3 font-mono text-xs"
                                            title={a.public_reference}
                                        >
                                            …{a.public_reference.slice(-8)}
                                        </td>
                                        <td className="p-3 font-medium">
                                            {a.name}
                                        </td>
                                        <td className="p-3">
                                            <div>{a.institution ?? '-'}</div>
                                            <div className="text-xs text-muted-foreground">
                                                {a.programme ?? '-'}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            {a.submitted_at
                                                ? new Date(
                                                      a.submitted_at,
                                                  ).toLocaleDateString()
                                                : '-'}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={statusVariant(
                                                    a.status,
                                                )}
                                            >
                                                {a.status.replaceAll('_', ' ')}
                                            </Badge>
                                        </td>
                                        <td className="p-3 text-right">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={`/admin/applications/${a.id}`}
                                                >
                                                    {a.status === 'submitted'
                                                        ? 'Open'
                                                        : 'View'}
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Applications.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Applications', href: '/admin/applications' },
    ],
};
