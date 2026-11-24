import type { ComponentProps } from 'react';
import { cn } from '@/lib/utils';

/** A plain <select> styled like the Input component, so it posts as an ordinary form field. */
export default function NativeSelect({
    className,
    ...props
}: ComponentProps<'select'>) {
    return (
        <select
            className={cn(
                'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}
