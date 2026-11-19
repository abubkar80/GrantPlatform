import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { dashboard } from '@/routes';

type Donor = {
    id: number;
    display_name: string;
    name: string;
    email: string;
    approval_status: 'pending' | 'approved' | 'declined';
    review_reason: string | null;
    reviewed_at: string | null;
    created_at: string;
};

type Props = {
    donors: Donor[];
    filter: 'pending' | 'approved' | 'declined' | 'all';
    counts: { pending: number; approved: number; declined: number };
};

const tabs: { key: Props['filter']; label: string }[] = [
    { key: 'pending', label: 'Pending' },
    { key: 'approved', label: 'Approved' },
    { key: 'declined', label: 'Declined' },
    { key: 'all', label: 'All' },
];

const statusVariant = {
    pending: 'secondary',
    approved: 'default',
    declined: 'destructive',
} as const;

function formatDate(value: string | null) {
    return value ? new Date(value).toLocaleDateString() : '-';
}

export default function DonorApprovals({ donors, filter, counts }: Props) {
    const { flash } = usePage().props as unknown as {
        flash: { status: string | null; error: string | null };
    };
    const [declining, setDeclining] = useState<Donor | null>(null);
    const [reason, setReason] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [busy, setBusy] = useState(false);

    const approve = (donor: Donor) => {
        setBusy(true);
        router.post(
            `/admin/donors/${donor.id}/approve`,
            {},
            { preserveScroll: true, onFinish: () => setBusy(false) },
        );
    };

    const submitDecline = () => {
        if (!declining) return;
        setBusy(true);
        router.post(
            `/admin/donors/${declining.id}/decline`,
            { reason },
            {
                preserveScroll: true,
                onError: (e) => setErrors(e),
                onSuccess: () => {
                    setDeclining(null);
                    setReason('');
                    setErrors({});
                },
                onFinish: () => setBusy(false),
            },
        );
    };

    return (
        <>
            <Head title="Donor approvals" />
            <div className="flex flex-col gap-4 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Donor approvals</h1>
                    <p className="text-sm text-muted-foreground">
                        Only approved donors can create funds or contribute.
                        Every decision is recorded in the audit log.
                    </p>
                </div>

                {flash?.status && (
                    <Alert>
                        <AlertDescription>{flash.status}</AlertDescription>
                    </Alert>
                )}
                {flash?.error && (
                    <Alert variant="destructive">
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap gap-2">
                    {tabs.map((tab) => (
                        <Button
                            key={tab.key}
                            asChild
                            variant={filter === tab.key ? 'default' : 'outline'}
                            size="sm"
                        >
                            <Link
                                href={`/admin/donors?status=${tab.key}`}
                                preserveScroll
                            >
                                {tab.label}
                                {tab.key !== 'all' && ` (${counts[tab.key]})`}
                            </Link>
                        </Button>
                    ))}
                </div>

                <Card>
                    <CardContent className="overflow-x-auto p-0">
                        <table className="w-full text-sm">
                            <thead className="border-b text-left text-muted-foreground">
                                <tr>
                                    <th className="p-3 font-medium">Donor</th>
                                    <th className="p-3 font-medium">Contact</th>
                                    <th className="p-3 font-medium">
                                        Registered
                                    </th>
                                    <th className="p-3 font-medium">Status</th>
                                    <th className="p-3 text-right font-medium">
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                {donors.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="p-6 text-center text-muted-foreground"
                                        >
                                            No donor registrations in this view.
                                        </td>
                                    </tr>
                                )}
                                {donors.map((donor) => (
                                    <tr
                                        key={donor.id}
                                        className="border-b last:border-0"
                                    >
                                        <td className="p-3">
                                            <div className="font-medium">
                                                {donor.display_name}
                                            </div>
                                            <div className="text-xs text-muted-foreground">
                                                {donor.name}
                                            </div>
                                        </td>
                                        <td className="p-3">{donor.email}</td>
                                        <td className="p-3">
                                            {formatDate(donor.created_at)}
                                        </td>
                                        <td className="p-3">
                                            <Badge
                                                variant={
                                                    statusVariant[
                                                        donor.approval_status
                                                    ]
                                                }
                                            >
                                                {donor.approval_status}
                                            </Badge>
                                            {donor.review_reason &&
                                                donor.approval_status ===
                                                    'declined' && (
                                                    <div className="mt-1 max-w-xs text-xs text-muted-foreground">
                                                        {donor.review_reason}
                                                    </div>
                                                )}
                                        </td>
                                        <td className="p-3 text-right">
                                            {donor.approval_status ===
                                            'pending' ? (
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        size="sm"
                                                        disabled={busy}
                                                        onClick={() =>
                                                            approve(donor)
                                                        }
                                                    >
                                                        Approve
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        disabled={busy}
                                                        onClick={() =>
                                                            setDeclining(donor)
                                                        }
                                                    >
                                                        Decline
                                                    </Button>
                                                </div>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">
                                                    Reviewed{' '}
                                                    {formatDate(
                                                        donor.reviewed_at,
                                                    )}
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>

            <Dialog
                open={declining !== null}
                onOpenChange={(open) => !open && setDeclining(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            Decline {declining?.display_name}?
                        </DialogTitle>
                        <DialogDescription>
                            The donor will see your reason. This decision cannot
                            be undone from this screen.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <label htmlFor="reason" className="text-sm font-medium">
                            Reason
                        </label>
                        <textarea
                            id="reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            rows={4}
                            className="w-full rounded-md border bg-transparent p-2 text-sm"
                            placeholder="Why is this registration being declined?"
                        />
                        {errors.reason && (
                            <p className="text-sm text-red-500">
                                {errors.reason}
                            </p>
                        )}
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeclining(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            disabled={busy}
                            onClick={submitDecline}
                        >
                            Decline registration
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

DonorApprovals.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'Donor approvals', href: '/admin/donors' },
    ],
};
