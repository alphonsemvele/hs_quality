import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

interface AuthUser {
    requires_mfa?: boolean;
    has_mfa_enrolled?: boolean;
}

interface PageProps {
    auth?: { user?: AuthUser | null };
    [key: string]: unknown;
}

const DISMISS_KEY = 'hsq.mfa-banner.snoozed-until';
const SNOOZE_HOURS = 24;

export default function MfaSetupBanner() {
    const { props } = usePage<PageProps>();
    const user = (props.auth as { user?: AuthUser | null } | undefined)?.user ?? null;
    const [dismissed, setDismissed] = useState(true);

    useEffect(() => {
        if (typeof window === 'undefined') return;
        const stored = window.localStorage.getItem(DISMISS_KEY);
        if (!stored) {
            setDismissed(false);
            return;
        }
        const ts = parseInt(stored, 10);
        if (Number.isFinite(ts) && ts > Date.now()) {
            setDismissed(true);
        } else {
            window.localStorage.removeItem(DISMISS_KEY);
            setDismissed(false);
        }
    }, []);

    if (!user) return null;
    if (!user.requires_mfa) return null;
    if (user.has_mfa_enrolled) return null;
    if (dismissed) return null;

    const snoozeLater = () => {
        if (typeof window === 'undefined') return;
        const until = Date.now() + SNOOZE_HOURS * 60 * 60 * 1000;
        window.localStorage.setItem(DISMISS_KEY, String(until));
        setDismissed(true);
    };

    return (
        <div
            role="status"
            aria-live="polite"
            className={cn(
                'border-b border-warning-200 bg-warning-50/90 px-4 py-2.5 backdrop-blur sm:px-6 lg:px-8',
                'dark:border-warning-700/40 dark:bg-warning-900/30',
            )}
        >
            <div className="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                <div className="flex items-start gap-3 sm:items-center">
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-lg bg-warning-100 text-warning-700 dark:bg-warning-900/50 dark:text-warning-300">
                        <ShieldIcon />
                    </span>
                    <div className="min-w-0">
                        <p className="text-sm font-semibold text-warning-900 dark:text-warning-100">
                            Double authentification non configurée
                        </p>
                        <p className="text-xs leading-relaxed text-warning-800/90 dark:text-warning-200/90">
                            Votre rôle implique l'accès à des données de santé. Activez le MFA TOTP pour protéger votre
                            compte — moins de 2 minutes.
                        </p>
                    </div>
                </div>
                <div className="flex shrink-0 items-center gap-2 self-end sm:self-auto">
                    <button
                        type="button"
                        onClick={snoozeLater}
                        className="rounded-md px-2.5 py-1.5 text-xs font-medium text-warning-700 transition-colors hover:bg-warning-100/60 dark:text-warning-300 dark:hover:bg-warning-900/40"
                    >
                        Plus tard
                    </button>
                    <Link
                        href="/dashboard/profile/mfa-setup"
                        className="inline-flex items-center gap-1.5 rounded-lg bg-warning-700 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-warning-800 dark:bg-warning-600 dark:hover:bg-warning-500"
                    >
                        Configurer maintenant
                        <ArrowIcon />
                    </Link>
                </div>
            </div>
        </div>
    );
}

function ShieldIcon() {
    return (
        <svg className="size-4" fill="none" stroke="currentColor" strokeWidth={1.75} viewBox="0 0 24 24">
            <rect x="3" y="11" width="18" height="11" rx="2" />
            <path d="M7 11V7a5 5 0 0110 0v4" />
        </svg>
    );
}

function ArrowIcon() {
    return (
        <svg className="size-3" fill="none" stroke="currentColor" strokeWidth={2.5} viewBox="0 0 24 24">
            <path d="M5 12h14m-7-7l7 7-7 7" strokeLinecap="round" strokeLinejoin="round" />
        </svg>
    );
}
