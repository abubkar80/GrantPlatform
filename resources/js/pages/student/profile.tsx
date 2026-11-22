import { Head, useForm } from '@inertiajs/react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';

type Option = { id: number; name: string };
type Programme = Option & { institution_id: number };

type Props = {
    profile: {
        institution_id: number | null;
        programme_id: number | null;
        matric_number: string | null;
        level: number | null;
        origin_lga_id: number | null;
        phone: string | null;
        enrolment_status: string;
    };
    institutions: Option[];
    programmes: Programme[];
    lgas: Option[];
};

const levels = [100, 200, 300, 400, 500, 600];

export default function StudentProfile({
    profile,
    institutions,
    programmes,
    lgas,
}: Props) {
    const form = useForm({
        institution_id: String(profile.institution_id ?? ''),
        programme_id: String(profile.programme_id ?? ''),
        matric_number: profile.matric_number ?? '',
        level: String(profile.level ?? ''),
        origin_lga_id: String(profile.origin_lga_id ?? ''),
        phone: profile.phone ?? '',
    });

    const visibleProgrammes = programmes.filter(
        (p) => String(p.institution_id) === form.data.institution_id,
    );

    return (
        <>
            <Head title="My profile" />
            <div className="flex max-w-3xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-2xl font-semibold">My profile</h1>
                    <Badge variant="secondary">
                        Enrolment: {profile.enrolment_status}
                    </Badge>
                </div>

                <FlashMessages />

                <Card>
                    <CardHeader>
                        <CardTitle>Your details</CardTitle>
                        <CardDescription>
                            Used to assess your application. Your enrolment is
                            confirmed by an Admin during review.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="grid gap-5 md:grid-cols-2"
                            onSubmit={(e) => {
                                e.preventDefault();
                                form.put('/student/profile', {
                                    preserveScroll: true,
                                });
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="institution_id">
                                    Institution
                                </Label>
                                <NativeSelect
                                    id="institution_id"
                                    value={form.data.institution_id}
                                    onChange={(e) =>
                                        form.setData((data) => ({
                                            ...data,
                                            institution_id: e.target.value,
                                            programme_id: '',
                                        }))
                                    }
                                >
                                    <option value="">Select institution</option>
                                    {institutions.map((i) => (
                                        <option key={i.id} value={i.id}>
                                            {i.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError
                                    message={form.errors.institution_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="programme_id">Programme</Label>
                                <NativeSelect
                                    id="programme_id"
                                    value={form.data.programme_id}
                                    disabled={form.data.institution_id === ''}
                                    onChange={(e) =>
                                        form.setData(
                                            'programme_id',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="">Select programme</option>
                                    {visibleProgrammes.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError
                                    message={form.errors.programme_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="matric_number">
                                    Matric number
                                </Label>
                                <Input
                                    id="matric_number"
                                    value={form.data.matric_number}
                                    onChange={(e) =>
                                        form.setData(
                                            'matric_number',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. GSU/2022/0001"
                                />
                                <InputError
                                    message={form.errors.matric_number}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="level">Level</Label>
                                <NativeSelect
                                    id="level"
                                    value={form.data.level}
                                    onChange={(e) =>
                                        form.setData('level', e.target.value)
                                    }
                                >
                                    <option value="">Select level</option>
                                    {levels.map((l) => (
                                        <option key={l} value={l}>
                                            {l} level
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError message={form.errors.level} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="origin_lga_id">
                                    Local government of origin
                                </Label>
                                <NativeSelect
                                    id="origin_lga_id"
                                    value={form.data.origin_lga_id}
                                    onChange={(e) =>
                                        form.setData(
                                            'origin_lga_id',
                                            e.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        Select local government
                                    </option>
                                    {lgas.map((l) => (
                                        <option key={l.id} value={l.id}>
                                            {l.name}
                                        </option>
                                    ))}
                                </NativeSelect>
                                <InputError
                                    message={form.errors.origin_lga_id}
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone number</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(e) =>
                                        form.setData('phone', e.target.value)
                                    }
                                    placeholder="08012345678"
                                />
                                <InputError message={form.errors.phone} />
                            </div>

                            <div className="md:col-span-2">
                                <Button
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    {form.processing && <Spinner />}
                                    Save profile
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

StudentProfile.layout = {
    breadcrumbs: [
        { title: 'Dashboard', href: dashboard() },
        { title: 'My profile', href: '/student/profile' },
    ],
};
