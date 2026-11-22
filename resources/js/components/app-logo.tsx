import { usePage } from '@inertiajs/react';

/** Placeholder brand mark: the name and logo are still to be decided, so this is one file to swap later. */
export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div
                className="flex size-9 items-center justify-center rounded-xl text-[13px] font-black text-black"
                style={{
                    background: 'linear-gradient(135deg,#ffc61d,#ea9010)',
                }}
            >
                HCM
            </div>
            <div className="ml-1 grid flex-1 text-left leading-tight">
                <span className="truncate text-sm font-semibold">{name}</span>
            </div>
        </>
    );
}
