import { router, usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';

interface ImpersonationProp {
    user_id: string;
    user_name: string;
    structure_id: string;
    structure_name: string;
}

interface PageProps extends InertiaPageProps {
    impersonation?: ImpersonationProp | null;
}

/**
 * Persistent red bar shown across every tenant page while a platform admin
 * is impersonating a specific tenant user. The bar is intentionally loud —
 * anyone walking past the screen should be able to see that the actions
 * being performed are not the named user's own.
 *
 * Hidden when the impersonation prop is absent (the default for everyone:
 * regular tenant users and platform admins outside of impersonation).
 */
export default function ImpersonationBanner() {
    const { props } = usePage<PageProps>();
    const impersonation = props.impersonation ?? null;

    if (!impersonation) return null;

    const onLeave = () => {
        router.post('/admin/impersonate/stop');
    };

    return (
        <div
            role="status"
            aria-live="polite"
            className="sticky top-16 z-30 flex items-center justify-between gap-3 border-b border-danger-700 bg-danger-600 px-4 py-2 text-sm font-medium text-white shadow-sm sm:px-6 lg:px-8"
        >
            <div className="flex min-w-0 items-center gap-2">
                <svg
                    aria-hidden
                    className="size-4 shrink-0"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth={2}
                    viewBox="0 0 24 24"
                >
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m0-9.36c-.59 0-1.16.24-1.58.66L1.92 12.6a2.23 2.23 0 000 3.15l8.5 8.5a2.23 2.23 0 003.16 0l8.5-8.5a2.23 2.23 0 000-3.15L13.58 4.05A2.23 2.23 0 0012 3.39z" />
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 17h.01" />
                </svg>
                <span className="truncate">
                    Mode superadmin — vous accédez au compte de <strong className="font-semibold">{impersonation.user_name}</strong> (
                    {impersonation.structure_name}). Toutes les actions sont auditées.
                </span>
            </div>
            <button
                type="button"
                onClick={onLeave}
                className="shrink-0 cursor-pointer rounded-md border border-white/40 bg-white/10 px-3 py-1 text-xs font-semibold text-white transition-colors hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
            >
                Quitter
            </button>
        </div>
    );
}
