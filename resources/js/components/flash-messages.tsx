import { usePage } from '@inertiajs/react';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function FlashMessages() {
    const { flash } = usePage().props as unknown as {
        flash?: { status: string | null; error: string | null };
    };

    return (
        <>
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
        </>
    );
}
